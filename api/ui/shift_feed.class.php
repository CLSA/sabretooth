<?php
/**
 * shift_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * datum shift feed
 * 
 * @package sabretooth\ui
 */
class shift_feed extends base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
    
    $session = bus\session::self();

    // determine the user id
    $this->user_id = $this->get_argument( 'user_id', NULL );
    if( 'operator' == $session->get_role()->name ) $this->user_id = $session->get_user()->id;
  }
  
  /**
   * Returns the data provided by this feed.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function get_data()
  {
    // determine from the start/end times whether this feed request is longer than a week
    $start = strtotime( $this->start_datetime );
    $end = strtotime( $this->end_datetime );
    $showing_month = 10 < ( ( $end - $start ) / 3600 / 24 );

    // create a list of shifts between the feed's start and end time
    $modifier = new db\modifier();
    $modifier->where( 'end_datetime', '>', $this->start_datetime );
    $modifier->where( 'start_datetime', '<', $this->end_datetime );
    if( is_null( $this->user_id ) )
      $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );
    else
      $modifier->where( 'user_id', '=', $this->user_id );
    
    $event_list = array();
    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      $datetime_obj = util::get_datetime_object( $db_shift->end_datetime );
      $end_time = '00' == $datetime_obj->format( 'i' )
                ? $datetime_obj->format( 'ga' )
                : $datetime_obj->format( 'g:ia' );

      // remove the m in am/pm
      $end_time = substr( $end_time, 0, -1 );

      $event_list[] = array(
        'id' => $db_shift->id,
        'title' => $showing_month
          ? sprintf( ' to %s %s', $end_time, $db_shift->get_user()->name )
          : $db_shift->get_user()->name,
        'allDay' => false,
        'start' => strtotime( $db_shift->start_datetime ),
        'end' => strtotime( $db_shift->end_datetime ) );
    }

    return $event_list;
  }

  /**
   * The user to restrict the list to.
   * @var int
   * @access protected
   */
  protected $user_id = NULL;
}
?>
