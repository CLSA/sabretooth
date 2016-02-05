<?php
/**
 * shift_template.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * shift_template: record
 */
class shift_template extends \cenozo\database\record
{
  /**
   * Overrides base method
   */
  public function save()
  {
    $start = util::get_datetime_object( $this->start_time );
    $end = util::get_datetime_object( $this->end_time );
    if( $start > $end )
      throw lib::create( 'exception\runtime',
        'Shift template start_time cannot be after the end_time', __METHOD__ );

    parent::save();
  }

  /**
   * Determines if the shift template lands on a particular date.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param \DateTime $datetime A date string in any valid PHP date time format.
   * @return boolean
   * @access public
   */
  public function match_date( $datetime )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query shift_template with no primary key.' );
      return false;
    }

    // make sure the date comes between the start and end dates
    if( $this->start_date <= $datetime &&
        ( is_null( $this->end_date ) || $this->end_date >= $datetime ) )
    {
      if( 'weekly' == $this->repeat_type )
      {
        // determine how many weeks from the start date this day is
        $weeks_apart = floor( $this->start_date->diff( $datetime )->days / 7 );

        if( 0 == $weeks_apart % $this->repeat_every )
        {
          // make sure the day of the week matches
          $weekday = strtolower( $datetime->format( 'l' ) );
          if( $this->$weekday ) return true;
        }
      }
      else if( 'day of month' == $this->repeat_type )
      {
        if( $datetime->format( 'j' ) == $this->start_date->format( 'j' ) ) return true;
      }
      else if( 'day of week' == $this->repeat_type )
      {
        if( $datetime->format( 'l' ) == $this->start_date->format( 'l' ) )
        {
          // determine which day of the week and week of the month the start date is on
          $last_month_datetime_obj = clone $datetime;
          $last_month_datetime_obj->sub( new \DateInterval( 'P1M' ) );
          $last_month_datetime_obj->setDate(
            $last_month_datetime_obj->format( 'Y' ),
            $last_month_datetime_obj->format( 'n' ),
            $last_month_datetime_obj->format( 't' ) );
          $week_number =
            $datetime->format( 'W' ) - $last_month_datetime_obj->format( 'W' );

          $last_month_datetime_obj = clone $this->start_date;
          $last_month_datetime_obj->sub( new \DateInterval( 'P1M' ) );
          $last_month_datetime_obj->setDate(
            $last_month_datetime_obj->format( 'Y' ),
            $last_month_datetime_obj->format( 'n' ),
            $last_month_datetime_obj->format( 't' ) );
          $start_week_number =
            $this->start_date->format( 'W' ) - $last_month_datetime_obj->format( 'W' );

          if( $week_number == $start_week_number ) return true;
        }
      }
    }

    // if we get here then there is no match
    return false;
  }
}
