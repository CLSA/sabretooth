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
   * Determines if the shift template lands on a particular date.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $date A date string in any valid PHP date time format.
   * @return boolean
   * @access public
   */
  public function match_date( $date )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query shift_template with no id.' );
      return false;
    } 

    $datetime_obj = util::get_datetime_object( $date );     
    $start_datetime_obj = util::get_datetime_object( $this->start_date );
    $end_datetime_obj = util::get_datetime_object( $this->end_date );

    // make sure the date comes between the start and end dates
    if( $start_datetime_obj <= $datetime_obj &&
        ( is_null( $this->end_date ) || $end_datetime_obj >= $datetime_obj ) )
    {
      if( 'weekly' == $this->repeat_type )
      {
        // determine how many weeks from the start date this day is
        $weeks_apart = floor( $start_datetime_obj->diff( $datetime_obj )->days / 7 );
        
        if( 0 == $weeks_apart % $this->repeat_every )
        {
          // make sure the day of the week matches
          $weekday = strtolower( $datetime_obj->format( 'l' ) );
          if( $this->$weekday ) return true;
        } 
      } 
      else if( 'day of month' == $this->repeat_type )
      {
        if( $datetime_obj->format( 'j' ) == $start_datetime_obj->format( 'j' ) ) return true;
      }   
      else if( 'day of week' == $this->repeat_type )
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
            
          if( $week_number == $start_week_number ) return true;
        } 
      } 
    }
  
    // if we get here then there is no match
    return false;
  }
}
?>
