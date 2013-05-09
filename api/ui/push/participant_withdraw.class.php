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

    if( $this->get_argument( 'cancel', false ) )
    { // if the most recent consent is a verbal negative, remove it
      $consent_mod = lib::create( 'database\modifier' );
      $consent_mod->order_desc( 'date' );
      $consent_mod->limit( 1 );
      $db_consent = current( $this->get_record()->get_consent_list( $consent_mod ) );
      if( $db_consent && !$db_consent->accept && !$db_consent->written )
      {
        $db_consent->delete();
      }
      else
      {
        throw lib::create( 'exception\runtime',
          sprintf( 'Trying to cancel withdraw for participant id %d but '.
                   'most recent consent is not a verbal negative.',
                   $this->get_record()->id ),
          __METHOD__ );
      }
    }
    else
    { // add a new consent to the participant
      $db_consent = lib::create( 'database\consent' );
      $db_consent->participant_id = $this->get_record()->id;
      $db_consent->accept = false;
      $db_consent->written = false;
      $db_consent->date = util::get_datetime_object()->format( 'Y-m-d' );
      $db_consent->note = 'Automatically added by the "withdraw" button.';
      $db_consent->save();
    }
  }
}
