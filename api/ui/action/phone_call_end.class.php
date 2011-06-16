<?php
/**
 * phone_call_end.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\action;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action phone_call end
 *
 * Assigns a participant to an phone_call.
 * @package sabretooth\ui
 */
class phone_call_end extends \sabretooth\ui\action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone_call', 'end', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $session = bus\session::self();
    $is_operator = 'operator' == $session->get_role()->name;

    // disconnect voip
    $voip_call = bus\voip_manager::self()->get_call();
    if( !is_null( $voip_call ) ) $voip_call->hang_up();

    if( $is_operator )
    { // set the end time and status of the call
      $db_phone_call = $session->get_current_phone_call();
      if( !is_null( $db_phone_call ) )
      {
        $date_obj = util::get_datetime_object();
        $db_phone_call->end_datetime = $date_obj->format( 'Y-m-d H:i:s' );
        $db_phone_call->status = $this->get_argument( 'status' );
        $db_phone_call->save();

        // if the status is "disconnected" or "wrong number" deactivate the phone and make a note
        // that the number has been disconnected
        if( 'disconnected' == $db_phone_call->status ||
            'wrong number' == $db_phone_call->status )
        {
          $db_phone = new db\phone( $db_phone_call->phone_id );
          if( !is_null( $db_phone ) )
          {
            $note = sprintf( 'This phone number has been disabled because a call was made to it '.
                             'on %s at %s '.
                             'by operator id %d (%s) '.
                             'with the result of "%s".',
                             util::get_formatted_date( $db_phone_call->end_datetime ),
                             util::get_formatted_time( $db_phone_call->end_datetime ),
                             $session->get_user()->id,
                             $session->get_user()->name,
                             $db_phone_call->status );
            $db_phone->active = false;
            $db_phone->note = is_null( $db_phone->note )
                              ? $note
                              : $db_phone->note."\n\n".$note;
            $db_phone->save();
          }
        }
      }
    }
  }
}
?>
