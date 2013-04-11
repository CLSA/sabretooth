<?php
/**
 * call_attempts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent form report data.
 * 
 * @abstract
 */
class call_attempts_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'call_attempts', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $restrict_source_id = $this->get_argument( 'restrict_source_id' );
   
    $this->add_title(
      sprintf( 'Participant\'s who have started but not finished the "%s" interview',
               $db_qnaire->name ) );

    $participant_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $participant_mod->where( 'participant_site.site_id', '=', $restrict_site_id );
    if( 0 < $restrict_source_id ) $participant_mod->where( 'source_id', '=', $restrict_source_id );

    // loop through every participant searching for those who have started an interview
    // which is not yet complete (restricting by site if necessary)
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $participant_list = $participant_class_name::select( $participant_mod );

    $contents = array();
    foreach( $participant_list as $db_participant )
    {
      $interview_mod = lib::create( 'database\modifier' );
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
      $interview_mod->where( 'completed', '=', false );
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
      if( $db_interview )
      {
        $total_calls = 0;

        $assignment_mod = lib::create( 'database\modifier' );
        $assignment_mod->where( 'end_datetime', '!=', NULL );
        $assignment_mod->order( 'start_datetime' );
        $db_last_assignment = NULL;
        foreach( $db_interview->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          $total_calls += $db_assignment->get_phone_call_count();
          $db_last_assignment = $db_assignment;
        }

        if( is_null( $db_last_assignment ) ) continue;

        // get the status of the last call from the last assignment
        $phone_call_mod = lib::create( 'database\modifier' );
        $phone_call_mod->order_desc( 'start_datetime' );
        $phone_call_mod->limit( 1 );
        $db_phone_call = current( $db_last_assignment->get_phone_call_list( $phone_call_mod ) );

        $contents[] = array(
          $db_participant->get_primary_site()->name,
          $db_participant->uid,
          $db_last_assignment->get_user()->first_name.' '.
            $db_last_assignment->get_user()->last_name,
          $db_last_assignment->start_datetime,
          $db_phone_call->status,
          $total_calls );
      }
    }
    
    $header = array(
      'Site',
      'UID',
      'Last Operator',
      'Last Call',
      'Call Result',
      '# Calls' );
    
    // remove the site if we are restricting the report
    if( $restrict_site_id )
    {
      array_shift( $header );
      foreach( $contents as $index => $content ) array_shift( $contents[$index] );
    }

    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
