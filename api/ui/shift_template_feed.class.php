<?php
/**
 * shift_template_feed.class.php
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
 * datum shift template feed
 * 
 * @package sabretooth\ui
 */
class shift_template_feed extends base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift template feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
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
    $event_list = array();
    $db_site = bus\session::self()->get_site();

    $calendar_start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $calendar_end_datetime_obj = util::get_datetime_object( $this->end_datetime );

    $modifier = new db\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_date', '<', $this->end_datetime );
    foreach( db\shift_template::select( $modifier ) as $db_shift_template )
    {
      for( $datetime_obj = clone $calendar_start_datetime_obj;
           $datetime_obj <= $calendar_end_datetime_obj;
           $datetime_obj->add( new \DateInterval( 'P1D' ) ) )
      {
        $add_event = false;
        $start_datetime_obj = util::get_datetime_object( $db_shift_template->start_date );
        $end_datetime_obj = util::get_datetime_object( $db_shift_template->end_date );
  
        // make sure this date is between the template's start and end date
        if( $start_datetime_obj <= $datetime_obj &&
            ( is_null( $db_shift_template->end_date ) || $end_datetime_obj >= $datetime_obj ) )
        {
          if( 'weekly' == $db_shift_template->repeat_type )
          {
            // determine how many weeks from the start date this day is
            $weeks_apart = floor( $start_datetime_obj->diff( $datetime_obj )->days / 7 );
          
            if( 0 == $weeks_apart % $db_shift_template->repeat_every )
            {
              // make sure the day of the week matches
              $weekday = strtolower( $datetime_obj->format( 'l' ) );
              if( $db_shift_template->$weekday ) $add_event = true;
            }
          }
          else if( 'day of month' == $db_shift_template->repeat_type )
          {
            if( $datetime_obj->format( 'j' ) == $start_datetime_obj->format( 'j' ) )
              $add_event = true;
          }
          else if( 'day of week' == $db_shift_template->repeat_type )
          {
            if( $datetime_obj->format( 'l' ) == $start_datetime_obj->format( 'l' ) )
            {
              // determine which day of the week and week of the month the start date is on
              $last_month_datetime_obj = clone $datetime_obj;
              $last_month_datetime_obj->sub( new \DateInterval( 'P1M' ) );
              $last_month_datetime_obj->setDate(
                $last_month_datetime_obj->format( 'Y' ),
                $last_month_datetime_obj->format( 'n' ),
                $last_month_datetime_obj->format( 't' ) );
              $week_number =
                $datetime_obj->format( 'W' ) - $last_month_datetime_obj->format( 'W' );
  
              $last_month_datetime_obj = clone $start_datetime_obj;
              $last_month_datetime_obj->sub( new \DateInterval( 'P1M' ) );
              $last_month_datetime_obj->setDate(
                $last_month_datetime_obj->format( 'Y' ),
                $last_month_datetime_obj->format( 'n' ),
                $last_month_datetime_obj->format( 't' ) );
              $start_week_number =
                $start_datetime_obj->format( 'W' ) - $last_month_datetime_obj->format( 'W' );
  
              if( $week_number == $start_week_number ) $add_event = true;
            }
          }
        }

        if( $add_event )
        {
          $event_start_datetime_obj = util::get_datetime_object(
            $datetime_obj->format( 'Y-m-d' ).$db_shift_template->start_time );
          $event_end_datetime_obj = util::get_datetime_object(
            $datetime_obj->format( 'Y-m-d' ).$db_shift_template->end_time );

          $end_time = '00' == $event_end_datetime_obj->format( 'i' )
                    ? $event_end_datetime_obj->format( 'ga' )
                    : $event_end_datetime_obj->format( 'g:ia' );

          $event_list[] = array(
            'id' => $db_shift_template->id,
            'title' => sprintf( ' to %s: %d operators',
              $end_time,
              $db_shift_template->operators ),
            'allDay' => false,
            'start' => $event_start_datetime_obj->format( \DateTime::ISO8601 ),
            'end' => $event_end_datetime_obj->format( \DateTime::ISO8601 ) );
        }
      }
    }

    return $event_list;
  }
}
?>
