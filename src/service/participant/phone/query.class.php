<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\participant\phone;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special participant for handling the query meta-resource
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    if( $this->get_argument( 'proxy', false ) )
    {
      $participant_id = $this->get_parent_record()->id;

      $modifier = clone $this->modifier;
      $modifier->left_join( 'participant', 'phone.participant_id', 'participant.id' );
      $modifier->left_join( 'alternate', 'phone.alternate_id', 'alternate.id' );
      $modifier->where_bracket( true );
      $modifier->where( 'participant.id', '=', $participant_id );
      $modifier->or_where( 'alternate.participant_id', '=', $participant_id );
      $modifier->where_bracket( false );

      // find aliases in the select and translate them in the modifier
      $this->select->apply_aliases_to_modifier( $modifier );

      $phone_class_name = lib::get_class_name( 'database\phone' );
      return $phone_class_name::count( $modifier );
    }
    else
    {
      return parent::get_record_count();
    }
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    // if in an assignment and the qnaire has alternate types then include them in the phone list
    $db_assignment = lib::create( 'business\session' )->get_current_assignment();
    $alternate_type_list = is_null( $db_assignment )
                         ? array()
                         : $db_assignment->get_interview()->get_qnaire()->get_alternate_type_object_list();

    if( 0 < count( $alternate_type_list ) )
    {
      $alternate_type_class_name = lib::get_class_name( 'database\alternate_type' );
      $participant_id = $this->get_parent_record()->id;

      // create a temporary table with all of the alternate information we'll need
      $alternate_sel = lib::create( 'database\select' );
      $alternate_mod = lib::create( 'database\modifier' );

      $alternate_sel->from( 'alternate' );
      $alternate_sel->add_column( 'id', 'alternate_id' );
      $alternate_sel->add_column( 'first_name' );
      $alternate_sel->add_column( 'last_name' );

      $coalesce_column_list = array();
      foreach( $alternate_type_list as $db_alternate_type )
      {
        // add whether the alternate is of this type
        $alternate_type_table_name = sprintf( 'alternate_has_%s_type', $db_alternate_type->name );
        $alternate_sel->add_table_column(
          $alternate_type_table_name,
          'alternate_id IS NOT NULL',
          sprintf( 'is_%s', $db_alternate_type->name )
        );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'alternate.id', '=', sprintf( '%s.alternate_id', $alternate_type_table_name ), false );
        $join_mod->where( sprintf( '%s.alternate_type_id', $alternate_type_table_name ), '=', $db_alternate_type->id );
        $alternate_mod->join_modifier( 'alternate_has_alternate_type', $join_mod, 'left', $alternate_type_table_name );

        // add whether the alternate has consent for this type
        if( is_null( $db_alternate_type->alternate_consent_type_id ) )
        {
          $alternate_sel->add_constant(
            false,
            sprintf( '%s_consent', $db_alternate_type->name ),
            'boolean'
          );
        }
        else
        {
          $consent_table_name = sprintf( 'alternate_last_%s_consent', $db_alternate_type->name );
          $alternate_sel->add_column(
            sprintf( 'IFNULL( %s_consent.accept, false )', $db_alternate_type->name ),
            sprintf( '%s_consent', $db_alternate_type->name ),
            false
          );
          $join_mod = lib::create( 'database\modifier' );
          $join_mod->where( 'alternate.id', '=', sprintf( '%s.alternate_id', $consent_table_name ), false );
          $join_mod->where(
            sprintf( '%s.alternate_consent_type_id', $consent_table_name ),
            '=',
            $db_alternate_type->alternate_consent_type_id
          );
          $alternate_mod->join_modifier( 'alternate_last_alternate_consent', $join_mod, 'left', $consent_table_name );
          $alternate_mod->left_join(
            'alternate_consent',
            sprintf( '%s.alternate_consent_id', $consent_table_name ),
            sprintf( '%s_consent.id', $db_alternate_type->name ),
            sprintf( '%s_consent', $db_alternate_type->name )
          );
        }

        // build the coalisce column
        $coalesce_column_list[] = sprintf( '%s.alternate_id', $alternate_type_table_name );
      }

      $alternate_mod->where( sprintf( 'COALESCE( %s )', implode( ', ', $coalesce_column_list ) ), '!=', NULL );
      $alternate_mod->where( 'alternate.participant_id', '=', $participant_id );

      $sql = 'CREATE TEMPORARY TABLE IF NOT EXISTS alternate_data ( alternate_id INT UNSIGNED NOT NULL, ';

      foreach( $alternate_type_list as $db_alternate_type )
      {
        $sql .= sprintf(
          '%s_title VARCHAR(255) NOT NULL, '.
          'is_%s TINYINT(1) NOT NULL, '.
          '%s_consent TINYINT(1) NOT NULL, ',
          $db_alternate_type->name,
          $db_alternate_type->name,
          $db_alternate_type->name
        );
      }

      $sql .= sprintf(
        'PRIMARY KEY( alternate_id ) ) %s %s',
        $alternate_sel->get_sql(),
        $alternate_mod->get_sql()
      );

      $alternate_type_class_name::db()->execute( $sql );

      $modifier = clone $this->modifier;
      $modifier->left_join( 'participant', 'phone.participant_id', 'participant.id' );
      $modifier->left_join( 'alternate_data', 'phone.alternate_id', 'alternate_data.alternate_id' );

      $modifier->where_bracket( true );
      $modifier->where( 'participant.id', '=', $participant_id );
      $modifier->or_where( 'alternate_data.alternate_id', '!=', NULL );
      $modifier->where_bracket( false );

      // find aliases in the select and translate them in the modifier
      $this->select->apply_aliases_to_modifier( $modifier );
      
      $concat_list = array();
      foreach( $alternate_type_list as $db_alternate_type )
      {
        $concat_list[] = sprintf(
          'IF( alternate_data.is_%s, CONCAT( "%s", IF( alternate_data.%s_consent, " with consent", "" ) ), NULL )',
          $db_alternate_type->name,
          $db_alternate_type->title,
          $db_alternate_type->name
        );
      }

      // add the person to the select
      $select = clone $this->select;
      $select->add_column(
        sprintf(
          'IFNULL( '.
            'CONCAT( '.
              // include the alternat's first and last name
              'alternate_data.first_name, " ", alternate_data.last_name, '.
              // include what alternate types and consent the alternate has
              '" [", CONCAT_WS( ", ", %s ), "]" '.
            '), '.
            // when not an alternate simply label the number as belonging to the participant
            '"Participant" '.
          ')',
          implode( ', ', $concat_list )
        ),
        'person',
        false
      );

      $phone_class_name = lib::get_class_name( 'database\phone' );
      return $phone_class_name::select( $select, $modifier );
    }
    else
    {
      return parent::get_record_list();
    }
  }
}
