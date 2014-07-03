<?php
/**
 * participant_reverse_withdraw.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant reverse_withdraw
 *
 * Edit a participant.
 */
class participant_reverse_withdraw extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'reverse_withdraw', $args );
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

    // if the most recent consent is a verbal negative, remove it
    $consent_mod = lib::create( 'database\modifier' );
    $consent_mod->order_desc( 'date' );
    $consent_mod->limit( 1 );
    $db_consent = current( $db_participant->get_consent_list( $consent_mod ) );
    if( $db_consent && !$db_consent->accept && !$db_consent->written )
    {
      $db_consent->delete();
    }
    else
    {
      throw lib::create( 'exception\runtime',
        sprintf( 'Trying to cancel reverse_withdraw for participant uid %s but '.
                 'most recent consent is not a verbal negative.',
                 $db_participant->uid ),
        __METHOD__ );
    }

    // find and remove the limesurvey withdraw survey information, if any exists
    $withdraw_manager = lib::create( 'business\withdraw_manager' );
    $withdraw_manager->remove_withdraw( $db_participant );

    // update this participant's queue status
    $db_participant->update_queue_status();
  }
}
