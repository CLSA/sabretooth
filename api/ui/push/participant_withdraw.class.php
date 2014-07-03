<?php
/**
 * participant_withdraw.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant withdraw
 *
 * Edit a participant.
 */
class participant_withdraw extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'withdraw', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $db_participant = $this->get_record();

    if( $this->get_argument( 'cancel', false ) )
    { // if the most recent consent is a verbal negative, remove it
      $consent_mod = lib::create( 'database\modifier' );
      $consent_mod->order_desc( 'date' );
      $consent_mod->limit( 1 );
      $db_consent = current( $db_participant->get_consent_list( $consent_mod ) );
      if( $db_consent && !$db_consent->accept && !$db_consent->written )
      {
        $db_consent->delete();
        $withdraw_manager = lib::create( 'business\withdraw_manager' );
        $withdraw_manager->remove_withdraw( $db_participant );
      }
      else
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'Trying to cancel withdraw for participant uid %s but '.
                   'most recent consent is not a verbal negative.',
                   $db_participant->uid ),
          __METHOD__ );
      }
    }
    else
    { // add a new consent to the participant
      $db_consent = lib::create( 'database\consent' );
      $db_consent->participant_id = $db_participant->id;
      $db_consent->accept = false;
      $db_consent->written = false;
      $db_consent->date = util::get_datetime_object()->format( 'Y-m-d' );
      $db_consent->note = 'Automatically added by the "withdraw" button.';
      $db_consent->save();
    }

    // update this participant's queue status
    $db_participant->update_queue_status();
  }
}
