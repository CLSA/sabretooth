<?php
/**
 * sample_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Sample report data.
 * 
 * @abstract
 */
class sample_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $database_class_name = lib::get_class_name( 'database\database' );
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_site = $restrict_site_id
             ? lib::create( 'database\site', $restrict_site_id )
             : NULL;
    $restrict_collection_id = $this->get_argument( 'restrict_collection_id', 0 );
    $db_collection = $restrict_collection_id
                   ? lib::create( 'database\collection', $restrict_collection_id )
                   : NULL;
    $application_id = lib::create( 'business\session' )->get_application()->id;
    
    $this->add_title( sprintf( 'For the %s interview', $db_qnaire->name ) ) ;
    $this->add_title( is_null( $db_site ) ?
      'for all sites' : sprintf( 'restricted to the %s site', $db_site->name ) );
    if( !is_null( $db_collection ) )
      $this->add_title( sprintf( 'restricted to the %s collection', $db_collection->name ) );
    
    // create a temporary participant site table
    $temp_site_mod = lib::create( 'database\modifier' );
    $temp_site_mod->where( 'application_id', '=', $application_id );
    $participant_class_name::db()->execute(
      'CREATE TEMPORARY TABLE temp_site '.
      'SELECT * FROM participant_site '.
      $temp_site_mod->get_sql() );
    $participant_class_name::db()->execute(
      'ALTER TABLE temp_site '.
      'ADD INDEX dk_participant_id ( participant_id ), '.
      'ADD INDEX dk_site_id ( site_id )' );

    // create a temporary participant primary address table
    $participant_class_name::db()->execute(
      'CREATE TEMPORARY TABLE temp_primary_address '.
      'SELECT * FROM participant_primary_address' );
    $participant_class_name::db()->execute(
      'ALTER TABLE temp_primary_address '.
      'ADD INDEX dk_participant_id ( participant_id ), '.
      'ADD INDEX dk_address_id ( address_id )' );

    // create a temporary last consent table
    $participant_class_name::db()->execute(
      'CREATE TEMPORARY TABLE temp_last_consent '.
      'SELECT * FROM participant_last_consent' );
    $participant_class_name::db()->execute(
      'ALTER TABLE temp_last_consent '.
      'ADD INDEX dk_participant_id ( participant_id )' );

    $select_sql =
      'SELECT uid, '.
        'cohort.name AS Cohort, '.
        ( is_null( $db_site ) ? 'site.name AS Site, ' : '' ).
        'participant.date_of_birth AS DOB, '.
        'TIMESTAMPDIFF( YEAR, participant.date_of_birth, CURDATE() ) AS Age, '.
        'participant.gender AS Sex, '.
        'IFNULL( language.code, "" ) AS Language, '.
        'region.abbreviation AS Province, '.
        'IFNULL( state.name, "" ) AS State, '.
        'IFNULL( DATE( require_event.datetime ), "n/a" ) AS "Previous Complete Date", '.
        'IFNULL( DATE( require_event.datetime + '.
                        'INTERVAL qnaire.delay WEEK + '.
                        'INTERVAL 1 DAY ), "n/a" ) AS "Date Available", '.
        'COUNT( phone_call.id ) AS "Number Of Calls", '.
        'IFNULL( DATE( appointment.datetime ), "" ) AS "Appointment", '.
        'IF( participant.email IS NULL, "no", "yes" ) AS "Email" ';

    $modifier = lib::create( 'database\modifier' );
    $modifier->cross_join( 'participant' );
    $join_mod = lib::create( 'database\modifier' );
    $modifier->join_modifier( 'application_has_participant', $join_mod );
    $join_mod->where( 'participant.id', '=', 'application_has_participant.participant_id', false );
    $join_mod->where( 'application_has_participant.datetime', '!=', NULL );
    $join_mod->where( 'application_has_participant.application_id', '=', $application_id );
    $modifier->join( 'cohort', 'participant.cohort_id', 'cohort.id' );
    $modifier->join( 'temp_site', 'participant.id', 'temp_site.participant_id' );
    $modifier->join( 'site', 'temp_site.site_id', 'site.id' );
    $modifier->left_join( 'temp_primary_address', 'temp_primary_address.participant_id', 'participant.id' );
    $modifier->left_join( 'address', 'temp_primary_address.address_id', 'address.id' );
    $modifier->left_join( 'region', 'address.region_id', 'region.id' );
    $modifier->left_join( 'state', 'participant.state_id', 'state.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'event.participant_id', false );
    $join_mod->where( 'event.event_type_id', '=', 'qnaire.completed_event_type_id', false );
    $modifier->left_join_modifier( 'event', $join_mod );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'require_event.participant_id', false );
    $join_mod->where( 'require_event.event_type_id', 'IN',
      'SELECT event_type_id FROM qnaire_has_event_type WHERE qnaire_id = qnaire.id', false );
    $modifier->left_join_modifier( 'event AS require_event', $join_mod );
    $modifier->left_join( 'interview', 'participant.id', 'interview.participant_id' );
    $modifier->left_join( 'assignment', 'interview.id', 'assignment.interview_id' );
    $modifier->left_join( 'phone_call', 'assignment.id', 'phone_call.assignment_id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'interview.id', '=', 'appointment.interview_id', false );
    $join_mod->where( 'appointment.assignment_id', '=', NULL );
    $modifier->left_join_modifier( 'appointment', $join_mod );
    $modifier->join( 'temp_last_consent', 'temp_last_consent.participant_id', 'participant.id' );
    $modifier->left_join( 'language', 'participant.language_id', 'language.id' );

    if( !is_null( $db_collection ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'collection_has_participant.participant_id', false );
      $join_mod->where( 'collection_has_participant.collection_id', '=', $db_collection->id );
      $modifier->join_modifier( 'collection_has_participant', $join_mod );
    }

    $modifier->where( 'participant.active', '=', true );
    $modifier->where( 'IFNULL( interview.completed, 0 )', '=', 0 );
    $modifier->where( 'qnaire.id', '=', $db_qnaire->id );
    $modifier->where_bracket( true );
    $modifier->where( 'require_event.id', '!=', NULL );
    $modifier->or_where(
      '( SELECT COUNT(*) FROM qnaire_has_event_type WHERE qnaire_id = qnaire.id )', '=', 0 );
    $modifier->where_bracket( false );
    if( !is_null( $db_site ) ) $modifier->where( 'site.id', '=', $db_site->id );
    $modifier->group( 'participant.uid' );

    $rows = $participant_class_name::db()->get_all(
      sprintf( '%s FROM qnaire %s',
               $select_sql,
               $modifier->get_sql() ) );

    $header = array();
    $content = array();
    foreach( $rows as $row )
    {   
      // set up the header
      if( 0 == count( $header ) ) 
        foreach( $row as $column => $value )
          $header[] = ucwords( str_replace( '_', ' ', $column ) );

      $content[] = array_values( $row );
    }   

    $this->add_table( NULL, $header, $content, NULL );

  }
}
