<?php
/**
 * shift_template_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: shift template feed
 */
class shift_template_feed extends \cenozo\ui\pull\base_feed
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift template feed.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
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

    $this->data = array();
    $db_site = lib::create( 'business\session' )->get_site();

    $daylight_savings = '1' == util::get_datetime_object()->format( 'I' );
    $calendar_start_datetime_obj = util::get_datetime_object( $this->start_datetime );
    $calendar_end_datetime_obj   = util::get_datetime_object( $this->end_datetime );

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site_id', '=', $db_site->id );
    $modifier->where( 'start_date', '<', $this->end_datetime );
    $class_name = lib::get_class_name( 'database\shift_template' );
    foreach( $class_name::select( $modifier ) as $db_shift_template )
    {
      for( $datetime_obj = clone $calendar_start_datetime_obj;
           $datetime_obj <= $calendar_end_datetime_obj;
           $datetime_obj->add( new \DateInterval( 'P1D' ) ) )
      {
        $add_event = false;
        $start_datetime_obj = util::get_datetime_object( $db_shift_template->start_date );
        $end_datetime_obj   = util::get_datetime_object( $db_shift_template->end_date );
  
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

          // need to adjust for daylight savings, but only if the server is currently in daylight
          // savings mode
          if( $daylight_savings )
          {
            $event_start_datetime_obj->sub( new \DateInterval( 'PT1H' ) );
            $event_end_datetime_obj->sub( new \DateInterval( 'PT1H' ) );
          }
          
          $end_time = '00' == $event_end_datetime_obj->format( 'i' )
                    ? $event_end_datetime_obj->format( 'ga' )
                    : $event_end_datetime_obj->format( 'g:ia' );

          $this->data[] = array(
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
  }
}
?>
