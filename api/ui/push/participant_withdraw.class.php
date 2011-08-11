<?php
/**
 * participant_withdraw.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: participant withdraw
 *
 * Edit a participant.
 * @package sabretooth\ui
 */
class participant_withdraw extends base_record_push
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
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    if( $this->get_argument( 'cancel', false ) )
    { // if the most recent consent is a withdraw, remove it
      $consent_mod = new db\modifier();
      $consent_mod->order_desc( 'date' );
      $consent_mod->limit( 1 );
      $db_consent = current( $this->get_record()->get_consent_list( $consent_mod ) );
      log::debug( $db_consent );
      if( $db_consent && 'withdraw' == $db_consent->event ) $db_consent->delete();
      else throw new exc\runtime(
        sprintf( 'Trying to cancel withdraw for participant id %d but most recent consent is not '.
                 'a withdraw.', $this->get_record()->id ), __METHOD__ );
    }
    else
    { // add a new withdraw consent to the participant
      $db_consent = new db\consent();
      $db_consent->participant_id = $this->get_record()->id;
      $db_consent->event = 'withdraw';
      $db_consent->date = util::get_datetime_object()->format( 'Y-m-d' );
      $db_consent->note = 'Automatically added by the "withdraw" button.';
      $db_consent->save();
    }
  }
}
?>
