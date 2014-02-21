<?php
/**
 * consent_required_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent required report data.
 * 
 * @abstract
 */
class consent_required_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary inrequiredation from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'consent_required', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    $session = lib::create( 'business\session' );

    if( $session->get_role()->all_sites )
    {
      $site_id = $this->get_argument( 'restrict_site_id' );
      $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
    }
    else
    {
      $db_site = $session->get_site(); 
    }

    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;

    if( 0 < strlen( $restrict_start_date ) )
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
    if( 0 < strlen( $restrict_end_date ) )
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
    if( 0 < strlen( $restrict_start_date ) && 0 < strlen( $restrict_end_date ) &&
        $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    $this->add_title( sprintf(
      'Interview complete, written consent outstanding%s',
      !is_null( $db_site ) ? ' for '.$db_site->name : '' ) );
    
    $contents = array();

    // loop through all participant who have completed an interview and have no written consent
    $participant_mod = lib::create( 'database\modifier' );
    $participant_mod->where( 'participant.active', '=', true );
    $participant_mod->where( 'participant.state_id', '=', NULL );
    $participant_mod->where( 'participant_last_written_consent.accept', '=', NULL );
    $participant_mod->where( 'interview.completed', '=', true );
    $participant_mod->where( 'interview.completed', '=', true );
    $participant_mod->order( 'uid' );

    // restrict by site, if necessary
    if( !is_null( $db_site ) )
      $participant_mod->where( 'participant_site.site_id', '=', $db_site->id );

    // restrict by date, if necessary
    if( !is_null( $start_datetime_obj ) || !is_null( $end_datetime_obj ) )
    {
      $participant_mod->where( 'interview.id', '=', 'interview_last_assignment.interview_id', false );
      $participant_mod->where( 'interview_last_assignment.assignment_id', '=', 'assignment.id', false );
      if( !is_null( $start_datetime_obj ) )
        $participant_mod->where(
          'assignment.end_datetime', '>=', $start_datetime_obj->format( 'Y-m-d' ).' 00:00:00' );
      if( !is_null( $end_datetime_obj ) )
        $participant_mod->where(
          'assignment.end_datetime', '<=', $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
    }

    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      $db_last_assignment = $db_participant->get_last_finished_assignment();
      $date = is_null( $db_last_assignment )
            ? 'Unknown'
            : util::get_datetime_object( $db_last_assignment->end_datetime )->format( 'Y-m-d' );
      $data = array(
        $db_participant->uid,
        $db_participant->first_name,
        $db_participant->last_name,
        $date );

      // if we are not restricting by site then list which site the participant belongs to
      if( is_null( $db_site ) )
        array_unshift( $data, $db_participant->get_effective_site()->name );

      $contents[] = $data;
    }
    
    $header = array(
      'UID',
      'First Name',
      'Last Name',
      'Interview Complete Date' );

    if( is_null( $db_site ) ) array_unshift( $header, 'Site' );
    
    $this->add_table( NULL, $header, $contents, NULL );
  }
}
