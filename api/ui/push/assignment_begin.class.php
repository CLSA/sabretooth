<?php
/**
 * assignment_begin.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: assignment begin
 *
 * Assigns a participant to an assignment.
 * @package sabretooth\ui
 */
class assignment_begin extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'assignment', 'begin', $args );

    // we can't use a transaction, otherwise the semaphore in the finish() method won't work right
    lib::create( 'business\session' )->set_use_transaction( false );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime exception\notice
   * @access public
   */
  public function finish()
  {
    $session = lib::create( 'business\session' );
    $setting_manager = lib::create( 'business\setting_manager' );

    if( !is_null( $session->get_current_assignment() ) )
      throw lib::create( 'exception\notice',
        'Please click the refresh button.  If this message appears more than twice '.
        'consecutively report this error to a superior.', __METHOD__ );
    
    // we need to use a semaphore to avoid race conditions
    $semaphore = sem_get( getmyinode() );
    if( !sem_acquire( $semaphore ) )
    {
      log::err( sprintf(
        'Unable to aquire semaphore for user id %d',
        $session->get_user()->id ) );
      throw lib::create( 'exception\notice',
        'The server is busy, please wait a few seconds then click the refresh button.',
        __METHOD__ );
    }

    // search through every queue for a new assignment until one is found
    $queue_mod = lib::create( 'database\modifier' );
    $queue_mod->where( 'rank', '!=', NULL );
    $queue_mod->order( 'rank' );
    $db_origin_queue = NULL;
    $db_participant = NULL;
    $db_appointment_id = NULL;
    $queue_class_name = lib::get_class_name( 'database\queue' );
    foreach( $queue_class_name::select( $queue_mod ) as $db_queue )
    {
      if( $setting_manager->get_setting( 'queue state', $db_queue->name ) )
      {
        $participant_mod = lib::create( 'database\modifier' );
        $participant_mod->limit( 1 );
        $db_queue->set_site( $session->get_site() );
        $participant_list = $db_queue->get_participant_list( $participant_mod );
        if( 1 == count( $participant_list ) )
        {
          $db_origin_queue = $db_queue;
          $db_participant = current( $participant_list );

          break;
        }
      }
    }

    if( is_null( $db_participant ) )
      throw lib::create( 'exception\notice',
        'There are no participants currently available.', __METHOD__ );
    
    // make sure the qnaire has phases
    $db_qnaire = lib::create( 'database\qnaire', $db_participant->current_qnaire_id );
    if( 0 == $db_qnaire->get_phase_count() )
      throw lib::create( 'exception\notice',
        'This participant\'s next questionnaire is not yet ready.  '.
        'Please immediately report this problem to a superior.',
        __METHOD__ );
    
    // get this participant's interview or create a new one if none exists yet
    $interview_mod = lib::create( 'database\modifier' );
    $interview_mod->where( 'participant_id', '=', $db_participant->id );
    $interview_mod->where( 'qnaire_id', '=', $db_participant->current_qnaire_id );

    $interview_class_name = lib::get_class_name( 'database\interview' );
    $db_interview_list = $interview_class_name::select( $interview_mod );
    
    if( 0 == count( $db_interview_list ) )
    {
      $db_interview = lib::create( 'database\interview' );
      $db_interview->participant_id = $db_participant->id;
      $db_interview->qnaire_id = $db_participant->current_qnaire_id;

      // Even though we have made sure this interview isn't a duplicate, it seems to happen from
      // time to time anyway, so catch it and tell the operator to try requesting the assignment
      // again
      try
      {
        $db_interview->save();
      }
      catch( \cenozo\exception\database $e )
      {
        if( $e->is_duplicate_entry() )
        {
          throw lib::create( 'exception\notice',
            'The server was too busy to assign a new participant, please wait a few seconds then '.
            'try requesting an assignment again.  If this message appears several times in a row '.
            'please report the error code to your superior.',
            __METHOD__ );
        }

        throw $e;
      }
    }
    else
    {
      $db_interview = $db_interview_list[0];
    }

    // create an assignment for this user
    $db_assignment = lib::create( 'database\assignment' );
    $db_assignment->user_id = $session->get_user()->id;
    $db_assignment->site_id = $session->get_site()->id;
    $db_assignment->interview_id = $db_interview->id;
    $db_assignment->queue_id = $db_origin_queue->id;
    $db_assignment->save();

    if( $db_origin_queue->from_appointment() )
    { // if this is an appointment queue, mark the appointment now associated with the appointment
      // this should always be the appointment with the earliest date
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'assignment_id', '=', NULL );
      $appointment_mod->order( 'datetime' );
      $appointment_mod->limit( 1 );
      $appointment_list = $db_participant->get_appointment_list( $appointment_mod );

      if( 0 == count( $appointment_list ) )
      {
        log::crit(
          'Participant queue is from an appointment but no appointment is found.', __METHOD__ );
      }
      else
      {
        $db_appointment = current( $appointment_list );
        $db_appointment->assignment_id = $db_assignment->id;
        $db_appointment->save();
      }
    }

    // release the semaphore
    if( !sem_release( $semaphore ) )
    {
      log::err( sprintf(
        'Unable to release semaphore for user id %d',
        $session->get_user()->id ) );
    }
  }
}
?>
