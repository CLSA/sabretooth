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
    if( $this->get_argument( 'proxy', false ) )
    {
      $alternate_type_class_name = lib::get_class_name( 'database\alternate_type' );
      $alternate_consent_type_class_name = lib::get_class_name( 'database\alternate_consent_type' );
      $participant_id = $this->get_parent_record()->id;
      $dm_type_id = $alternate_type_class_name::get_unique_record( 'name', 'proxy' )->id;
      $ip_type_id = $alternate_type_class_name::get_unique_record( 'name', 'informant' )->id;
      $dm_consent_type_id = $alternate_consent_type_class_name::get_unique_record( 'name', 'decision maker' )->id;
      $ip_consent_type_id = $alternate_consent_type_class_name::get_unique_record( 'name', 'information provider' )->id;

      // create a temporary table with all of the alternate information we'll need
      $alternate_sel = lib::create( 'database\select' );
      $alternate_sel->from( 'alternate' );
      $alternate_sel->add_column( 'id', 'alternate_id' );
      $alternate_sel->add_column( 'first_name' );
      $alternate_sel->add_column( 'last_name' );
      $alternate_sel->add_table_column( 'alternate_has_dm_type', 'alternate_id IS NOT NULL', 'is_dm' );
      $alternate_sel->add_column( 'IFNULL( dm_consent.accept, false )', 'dm_consent', false );
      $alternate_sel->add_table_column( 'alternate_has_ip_type', 'alternate_id IS NOT NULL', 'is_ip' );
      $alternate_sel->add_column( 'IFNULL( ip_consent.accept, false )', 'ip_consent', false );

      $alternate_mod = lib::create( 'database\modifier' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_has_dm_type.alternate_id', false );
      $join_mod->where( 'alternate_has_dm_type.alternate_type_id', '=', $dm_type_id );
      $alternate_mod->join_modifier( 'alternate_has_alternate_type', $join_mod, 'left', 'alternate_has_dm_type' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_has_ip_type.alternate_id', false );
      $join_mod->where( 'alternate_has_ip_type.alternate_type_id', '=', $ip_type_id );
      $alternate_mod->join_modifier( 'alternate_has_alternate_type', $join_mod, 'left', 'alternate_has_ip_type' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_last_dm_consent.alternate_id', false );
      $join_mod->where( 'alternate_last_dm_consent.alternate_consent_type_id', '=', $dm_consent_type_id );
      $alternate_mod->join_modifier( 'alternate_last_alternate_consent', $join_mod, 'left', 'alternate_last_dm_consent' );
      $alternate_mod->left_join(
        'alternate_consent',
        'alternate_last_dm_consent.alternate_consent_id',
        'dm_consent.id',
        'dm_consent'
      );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_last_ip_consent.alternate_id', false );
      $join_mod->where( 'alternate_last_ip_consent.alternate_consent_type_id', '=', $ip_consent_type_id );
      $alternate_mod->join_modifier( 'alternate_last_alternate_consent', $join_mod, 'left', 'alternate_last_ip_consent' );
      $alternate_mod->left_join(
        'alternate_consent',
        'alternate_last_ip_consent.alternate_consent_id',
        'ip_consent.id',
        'ip_consent'
      );
      $alternate_mod->where( 'COALESCE( alternate_has_dm_type.alternate_id, alternate_has_ip_type.alternate_id )', '!=', NULL );
      $alternate_mod->where( 'alternate.participant_id', '=', $participant_id );

      $alternate_type_class_name::db()->execute( sprintf(
        'CREATE TEMPORARY TABLE IF NOT EXISTS alternate_data ( '.
          'alternate_id INT UNSIGNED NOT NULL, '.
          'is_dm TINYINT(1) NOT NULL, '.
          'dm_consent TINYINT(1) NOT NULL, '.
          'is_ip TINYINT(1) NOT NULL, '.
          'ip_consent TINYINT(1) NOT NULL, '.
          'PRIMARY KEY( alternate_id ) '.
        ') %s %s',
        $alternate_sel->get_sql(),
        $alternate_mod->get_sql()
      ) );

      $modifier = clone $this->modifier;
      $modifier->left_join( 'participant', 'phone.participant_id', 'participant.id' );
      $modifier->left_join( 'alternate_data', 'phone.alternate_id', 'alternate_data.alternate_id' );

      $modifier->where_bracket( true );
      $modifier->where( 'participant.id', '=', $participant_id );
      $modifier->or_where( 'alternate_data.alternate_id', '!=', NULL );
      $modifier->where_bracket( false );

      // find aliases in the select and translate them in the modifier
      $this->select->apply_aliases_to_modifier( $modifier );
      
      // add the person to the select
      $select = clone $this->select;
      $select->add_column(
        'IFNULL( '.
          'CONCAT( '.
            // include the alternat's first and last name
            'alternate_data.first_name, " ", alternate_data.last_name, '.
            // include whether the alternate is a DM, IP or both
            '" [", '.
            'IF( '.
              'alternate_data.is_dm AND alternate_data.is_ip, '.
              'CONCAT( '.
                'CONCAT( "DM", IF( alternate_data.dm_consent, " with consent", "" ) ), '.
                '", ", '.
                'CONCAT( "IP", IF( alternate_data.ip_consent, " with consent", "" ) ) '.
              '), '.
              'IF( '.
                'alternate_data.is_dm, '.
                'CONCAT( "DM", IF( alternate_data.dm_consent, " with consent", "" ) ), '.
                'CONCAT( "IP", IF( alternate_data.ip_consent, " with consent", "" ) ) '.
              ') '.
            '), '.
            '"]" '.
          '), '.
          // when not an alternate simply label the number as belonging to the participant
          '"Participant" '.
        ')',
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
