<?php
/**
 * availability_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: availability feed
 */
class availability_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the availability feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
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

    $availability_class_name = lib::get_class_name( 'database\availability' );

    $this->data = array();
    $db_site = lib::create( 'business\session' )->get_site();

    $start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $end_datetime_obj   = util::get_datetime_object( $this->end_datetime );

    $days = array();
    $current_datetime_obj = clone $start_datetime_obj;
    while( $current_datetime_obj->diff( $end_datetime_obj )->days )
    {
      $days[ $current_datetime_obj->format( 'Y-m-d' ) ] = array(
        'dow' => strtolower( $current_datetime_obj->format( 'l' ) ),
        'diffs' => array(),
        'times' => array() );
      $current_datetime_obj->add( new \DateInterval( 'P1D' ) );
    }

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'participant_site.site_id', '=', $db_site->id );
    $modifier->where( 'interview.completed', '=', false );
    foreach( $availability_class_name::select( $modifier ) as $db_availability )
    {
      foreach( $days as $date => $day )
      {
        $diffs = &$days[$date]['diffs'];

        if( $db_availability->$day['dow'] )
        {
          $start_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_availability->start_time, 0, -3 ) ) );
          if( !array_key_exists( $start_time_as_int, $diffs ) ) $diffs[ $start_time_as_int ] = 0;
          $diffs[ $start_time_as_int ] += 1;

          $end_time_as_int =
            intval( preg_replace( '/[^0-9]/', '',
              substr( $db_availability->end_time, 0, -3 ) ) );
          if( !array_key_exists( $end_time_as_int, $diffs ) ) $diffs[ $end_time_as_int ] = 0;
          $diffs[ $end_time_as_int ] -= 1;
        }

        // unset diffs since it is a reference
        unset( $diffs );
      }
    }

    // use the 'diff' arrays to define the 'times' array
    foreach( $days as $date => $day )
    {
      $num_participants = 0;
      $diffs = &$days[$date]['diffs'];
      $times = &$days[$date]['times'];

      if( 0 < count( $diffs ) )
      {
        // sort the diff array by key (time) to make the following for-loop nice and simple
        ksort( $diffs );

        foreach( $diffs as $time => $diff )
        {
          $num_participants += $diff;
          $times[$time] = $num_participants;
        }
      }

      // unset times since it is a reference
      unset( $times );
    }

    // finally, construct the event list using the 'times' array
    $start_time = false;
    $available = 0;
    $this->data = array();
    foreach( $days as $date => $day )
    {
      foreach( $day['times'] as $time => $number )
      {
        if( $number == $available ) continue;

        $minutes = $time % 100;
        $hours = ( $time - $minutes ) / 100;
        $time_string = sprintf( '%02d:%02d', $hours, $minutes );
        if( $start_time )
        {
          $end_time = $time_string;
     
          if( $available )
          {   
            $end_time_for_title =
              sprintf( '%s%s%s',
                       $hours > 12 ? $hours - 12 : $hours,
                       $minutes ? ':'.sprintf( '%02d', $minutes ) : '', 
                       $hours > 12 ? 'p' : 'a' );
            $this->data[] = array(
              'title' => sprintf( ' to %s: %d', $end_time_for_title, $available ),
              'allDay' => false,
              'start' => $date.' '.$start_time,
              'end' => $date.' '.$end_time );
          }
        }

        // only use this time as the next start time if the available number is not 0
        $start_time = 0 < $number ? $time_string : false;
        $available = $number;
      }
    }
  }
}
?>
