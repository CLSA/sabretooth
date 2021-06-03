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
      $alternate_consent_type_class_name = lib::get_class_name( 'database\alternate_consent_type' );
      $participant_id = $this->get_parent_record()->id;
      $dm_alternate_consent_type_id = $alternate_consent_type_class_name::get_unique_record( 'name', 'decision maker' )->id;
      $ip_alternate_consent_type_id = $alternate_consent_type_class_name::get_unique_record( 'name', 'information provider' )->id;

      $modifier = clone $this->modifier;
      $modifier->left_join( 'participant', 'phone.participant_id', 'participant.id' );
      $modifier->left_join( 'alternate', 'phone.alternate_id', 'alternate.id' );
      
      // include the dm consent
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_last_dm_consent.alternate_id', false );
      $join_mod->where( 'alternate_last_dm_consent.alternate_consent_type_id', '=', $dm_alternate_consent_type_id );
      $modifier->join_modifier( 'alternate_last_alternate_consent', $join_mod, 'left', 'alternate_last_dm_consent' );
      $modifier->left_join(
        'alternate_consent',
        'alternate_last_dm_consent.alternate_consent_id',
        'dm_consent.id',
        'dm_consent'
      );

      // include the ip consent
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'alternate.id', '=', 'alternate_last_ip_consent.alternate_id', false );
      $join_mod->where( 'alternate_last_ip_consent.alternate_consent_type_id', '=', $ip_alternate_consent_type_id );
      $modifier->join_modifier( 'alternate_last_alternate_consent', $join_mod, 'left', 'alternate_last_ip_consent' );
      $modifier->left_join(
        'alternate_consent',
        'alternate_last_ip_consent.alternate_consent_id',
        'ip_consent.id',
        'ip_consent'
      );

      $modifier->where_bracket( true );
      $modifier->where( 'participant.id', '=', $participant_id );
      $modifier->where_bracket( true, true );
      $modifier->where( 'alternate.participant_id', '=', $participant_id );
      $modifier->where_bracket( true );
      $modifier->where( 'alternate.proxy', '=', true );
      $modifier->or_where( 'alternate.informant', '=', true );
      $modifier->where_bracket( false );
      $modifier->where_bracket( false );
      $modifier->where_bracket( false );

      // find aliases in the select and translate them in the modifier
      $this->select->apply_aliases_to_modifier( $modifier );
      
      // add the person to the select
      $select = clone $this->select;
      $select->add_column(
        'IFNULL( '.
          'CONCAT( '.
            // include the alternat's first and last name
            'alternate.first_name, " ", alternate.last_name, '.
            // include whether the alternate is a DM, IP or both
            '" [", '.
            'IF( '.
              'proxy AND informant, '.
              'CONCAT( '.
                'CONCAT( "DM", IF( IFNULL( dm_consent.accept, false ), " with consent", "" ) ), '.
                '", ", '.
                'CONCAT( "IP", IF( IFNULL( ip_consent.accept, false ), " with consent", "" ) ) '.
              '), '.
              'IF( '.
                'proxy, '.
                'CONCAT( "DM", IF( IFNULL( dm_consent.accept, false ), " with consent", "" ) ), '.
                'CONCAT( "IP", IF( IFNULL( ip_consent.accept, false ), " with consent", "" ) ) '.
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
