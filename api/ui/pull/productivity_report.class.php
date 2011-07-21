<?php
/**
 * productivity_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Productivity report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class productivity_report extends base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'productivity', $args );
  }

  public function finish()
  {
    $db_role = db\role::get_unique_record( 'name', 'operator' );

    // get the operation's arguments
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $single_date = $this->get_argument( 'date' );
    if( $single_date ) $single_datetime_obj = util::get_datetime_object( $single_date );
    
    // set the title and sub title(s)
    $title = ( $single_date ? 'Daily' : 'Overall' ).' Productivity Report';
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title .= ' for '.$db_site->name;
    }

    $this->add_title( $title );

    if( $single_date )
      $this->add_title( $single_datetime_obj->format( 'l, F jS, Y' ) );
    
    // now create a table for every site included in the report
    $site_mod = new db\modifier();
    if( $restrict_site_id ) $site_mod->where( 'id', '=', $restrict_site_id );
    foreach( db\site::select( $site_mod ) as $db_site )
    {
      $contents = array();
      // start by determining the table contents
      foreach( db\user::select() as $db_user )
      {
        // make sure the operator has min/max time for this date range
        $activity_mod = new db\modifier();
        $activity_mod->where( 'user_id', '=', $db_user->id );
        $activity_mod->where( 'site_id', '=', $db_site->id );
        $activity_mod->where( 'role_id', '=', $db_role->id );

        if( $single_date )
        {
          // get the min and max datetimes for this day
          $activity_mod->where( 'datetime', '>=',
            $single_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $activity_mod->where( 'datetime', '<=',
            $single_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }

        $start_datetime_obj = db\activity::get_min_datetime( $activity_mod );
        $end_datetime_obj = db\activity::get_max_datetime( $activity_mod );
        
        // if there is no activity then skip this user
        if( is_null( $start_datetime_obj ) || is_null( $end_datetime_obj ) ) continue;
        
        // Determine the number of completed interviews and their average length.
        // This is done by looping through all of this user's assignments.  Any assignment's who's
        // interview is completed is tested to see if that interview's last assignment is the
        // originating assignment.
        ///////////////////////////////////////////////////////////////////////////////////////////
        $assignment_mod = new db\modifier();
        if( $single_date )
        {
          $assignment_mod->where( 'start_datetime', '>=',
            $single_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $assignment_mod->where( 'start_datetime', '<=',
            $single_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }
        
        $completes = 0;
        $interview_time = 0;
        foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          $db_interview = $db_assignment->get_interview();
          if( $db_interview->completed )
          {
            $last_assignment_mod = new db\modifier();
            $last_assignment_mod->where( 'interview_id', '=', $db_interview->id );
            $last_assignment_mod->order_desc( 'start_datetime' );
            $last_assignment_mod->limit( 1 );
            $db_last_assignment = current( db\assignment::select( $last_assignment_mod ) );
            if( $db_assignment->id == $db_last_assignment->id )
            {
              $completes++;

              foreach( $db_interview->get_qnaire()->get_phase_list() as $db_phase )
              {
                // only count the time in non-repeating phases
                if( !$db_phase->repeated )
                  $interview_time += $db_interview->get_interview_time( $db_phase );
              }
            }
          }
        }

        // Determine the total working time.
        // This is done by finding the minimum and maximum activity time for every day included in
        // the report and calculating the difference between the two times.
        ///////////////////////////////////////////////////////////////////////////////////////////
        $total_time = 0;
        $start_datetime_obj->setTime( 0, 0 );
        $end_datetime_obj->setTime( 0, 0 );
        $interval = new \DateInterval( 'P1D' );
        for( $datetime_obj = clone $start_datetime_obj;
             $datetime_obj <= $end_datetime_obj;
             $datetime_obj->add( $interval ) )
        {
          // if reporting a single date restrict the count to that day only
          if( $single_date && $single_datetime_obj != $datetime_obj ) continue;

          $day_activity_mod = new db\modifier();
          $day_activity_mod->where( 'user_id', '=', $db_user->id );
          $day_activity_mod->where( 'site_id', '=', $db_site->id );
          $day_activity_mod->where( 'role_id', '=', $db_role->id );
          $day_activity_mod->where( 'datetime', '>=',
            $datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $day_activity_mod->where( 'datetime', '<=',
            $datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          
          $min_datetime_obj = db\activity::get_min_datetime( $day_activity_mod );
          $max_datetime_obj = db\activity::get_max_datetime( $day_activity_mod );
          
          if( !is_null( $min_datetime_obj ) && !is_null( $max_datetime_obj ) )
          {
            $diff_obj = $max_datetime_obj->diff( $min_datetime_obj );
            $total_time += $diff_obj->h + round( $diff_obj->i / 15 ) * 0.25;
          }
        }

        // Now we can use all the information gathered above to fill in the contents of the table.
        ///////////////////////////////////////////////////////////////////////////////////////////
        if( $single_date )
        {
          $contents[] = array(
            $db_user->first_name.' '.$db_user->last_name,
            $completes,
            $min_datetime_obj->format( "H:i" ),
            $max_datetime_obj->format( "H:i" ),
            $total_time,
            $total_time > 0 ? sprintf( '%0.2f', $completes / $total_time ) : '',
            $completes > 0 ? sprintf( '%0.2f', $interview_time / $completes / 60 ) : '' );
        }
        else
        {
          $contents[] = array(
            $db_user->first_name.' '.$db_user->last_name,
            $completes,
            $total_time,
            $total_time > 0 ? sprintf( '%0.2f', $completes / $total_time ) : '',
            $completes > 0 ? sprintf( '%0.2f', $interview_time / $completes / 60 ) : '' );
        }
      }

      if( $single_date )
      {
        $header = array(
          "Operator",
          "Completes",
          "Start Time",
          "End Time",
          "Total Time",
          "CPH",
          "Avg. Length" );

        $footer = array(
          "Total",
          "sum()",
          "--",
          "--",
          "sum()",
          "average()",
          "average()" );
      }
      else
      {
        $header = array(
          "Operator",
          "Completes",
          "Total Time",
          "CPH",
          "Avg. Length" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          "average()",
          "average()" );
      }

      $title = 0 == $restrict_site_id ? $db_site->name : NULL;
      $this->add_table( $title, $header, $contents, $footer );
    }

    return parent::finish();
  }
}
?>
