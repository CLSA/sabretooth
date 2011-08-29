<?php
/**
 * call_attempts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Consent form report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class call_attempts_report extends base_report
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

  public function finish()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = new db\qnaire( $this->get_argument( 'qnaire_id' ) );
    
    $title = 'Call Attempts Report';
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_site->name;
    }

    $this->add_title( $title );
    $this->add_title(
      sprintf( 'Participant\'s who have started but not finished the "%s" interview',
               $db_qnaire->name ) );

    $contents = array();

    // loop through every participant searching for those who have started an interview
    // which is not yet complete (restricting by site if necessary)
    $participant_list = $restrict_site_id
                      ? db\participant::select_for_site( $db_site )
                      : db\participant::select();

    foreach( $participant_list as $db_participant )
    {
      $interview_mod = new db\modifier();
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
      $interview_mod->where( 'completed', '=', false );
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
      if( $db_interview )
      {
        $total_calls = 0;

        $assignment_mod = new db\modifier();
        $assignment_mod->where( 'end_datetime', '!=', NULL );
        $assignment_mod->order( 'start_datetime' );
        foreach( $db_interview->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          $total_calls += $db_assignment->get_phone_call_count();
        }

        // get the status of the last call from the last assignment
        $phone_call_mod = new db\modifier();
        $phone_call_mod->order_desc( 'start_datetime' );
        $phone_call_mod->limit( 1 );
        $db_phone_call = current( $db_assignment->get_phone_call_list( $phone_call_mod ) );

        $contents[] = array(
          $db_participant->get_primary_site()->name,
          $db_participant->uid,
          $db_assignment->get_user()->first_name.' '.$db_assignment->get_user()->last_name,
          $db_assignment->start_datetime,
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

    return parent::finish();
  }
}
?>
