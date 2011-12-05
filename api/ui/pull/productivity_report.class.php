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
    // determine whether or not to round time to 15 minute increments
    $round_times = $this->get_argument( 'round_times', true );

    $db_role = db\role::get_unique_record( 'name', 'operator' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = new db\modifier();
    if( $restrict_site_id ) 
      $site_mod->where( 'id', '=', $restrict_site_id );
    
    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;
    
    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj )
        $start_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj )
        $end_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    // determine whether we are running the report for a single date or not
    $single_date = ( !is_null( $start_datetime_obj ) &&
                     !is_null( $end_datetime_obj ) &&
                     $start_datetime_obj == $end_datetime_obj ) || 
                   ( !is_null( $start_datetime_obj ) &&
                     $start_datetime_obj == $now_datetime_obj );
    if( $single_date ) $single_datetime_obj = clone $start_datetime_obj;

    $db_qnaire = new db\qnaire( $this->get_argument( 'restrict_qnaire_id' ) );
    
    $this->add_title( 
      sprintf( 'Operator productivity for '.
               'the %s interview', $db_qnaire->name ) ) ;
    
    // we define the min and max datetime objects here, they get set in the next foreach loop, then
    // used in the for loop below
    $min_datetime_obj = NULL;
    $max_datetime_obj = NULL;
          
    // now create a table for every site included in the report
    foreach( db\site::select( $site_mod ) as $db_site )
    {
      $contents = array();
      // start by determining the table contents
      $grand_total_time = 0;
      $grand_total_completes = 0;
      $grand_total_calls = 0;
      foreach( db\user::select() as $db_user )
      {
        // make sure the operator has min/max time for this date range
        $activity_mod = new db\modifier();
        $activity_mod->where( 'user_id', '=', $db_user->id );
        $activity_mod->where( 'site_id', '=', $db_site->id );
        $activity_mod->where( 'role_id', '=', $db_role->id );
        $activity_mod->where( 'operation.subject', '!=', 'self' );

        $assignment_mod = new db\modifier();
        if( $restrict_start_date && $restrict_end_date )
        {
          $activity_mod->where( 'datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $activity_mod->where( 'datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          $assignment_mod->where( 'start_datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $assignment_mod->where( 'end_datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }
        else if( $restrict_start_date && !$restrict_end_date ) 
        {
          $activity_mod->where( 'datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $assignment_mod->where( 'start_datetime', '>=',
            $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
        }
        else if( !$restrict_start_date && $restrict_end_date )
        {
          $activity_mod->where( 'datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          $assignment_mod->where( 'start_datetime', '<=',
            $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
        }

        $min_activity_datetime_obj = db\activity::get_min_datetime( $activity_mod );
        $max_activity_datetime_obj = db\activity::get_max_datetime( $activity_mod );
        
        // if there is no activity then skip this user
        if( is_null( $min_activity_datetime_obj ) || 
            is_null( $max_activity_datetime_obj ) ) continue;
        
        // Determine the number of completed interviews and their average length.
        // This is done by looping through all of this user's assignments.  Any assignment
        // with an interview that is completed is tested to see if that interview's last 
        // assignment is the originating assignment.
        ///////////////////////////////////////////////////////////////////////////////////////////
        
        $completes = 0;
        $interview_time = 0;
        $calls = 0;
        foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          $db_interview = $db_assignment->get_interview();
          $calls += $db_assignment->get_phone_call_count();
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
        } // end loop on assignments

        // Determine the total working time.
        // This is done by finding the minimum and maximum activity time for every day included in
        // the report and calculating the difference between the two times.
        ///////////////////////////////////////////////////////////////////////////////////////////
        $time = 0;
        $total_time = 0;
        $min_activity_datetime_obj->setTime( 0, 0 );
        $max_activity_datetime_obj->setTime( 0, 0 );
        $interval = new \DateInterval( 'P1D' );
        for( $datetime_obj = clone $min_activity_datetime_obj;
             $datetime_obj <= $max_activity_datetime_obj;
             $datetime_obj->add( $interval ) )
        {
          // if reporting a single date restrict the count to that day only
          if( $single_date && $single_datetime_obj != $datetime_obj ) continue;

          // get the elapsed time and round to 15 minute increments (if necessary)
          $time += db\activity::get_elapsed_time(
            $db_user, $db_site, $db_role, $datetime_obj->format( 'Y-m-d' ) );
          $total_time = $round_times ? floor( 4 * $time ) / 4 : $time;
        }

        // Now we can use all the information gathered above to fill in the contents of the table.
        ///////////////////////////////////////////////////////////////////////////////////////////
        if( $single_date )
        {
          $day_activity_mod = new db\modifier();
          $day_activity_mod->where( 'user_id', '=', $db_user->id );
          $day_activity_mod->where( 'site_id', '=', $db_site->id );
          $day_activity_mod->where( 'role_id', '=', $db_role->id );
          $day_activity_mod->where( 'operation.subject', '!=', 'self' );
          $day_activity_mod->where( 'datetime', '>=',
            $min_activity_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
          $day_activity_mod->where( 'datetime', '<=',
            $min_activity_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
          
          $min_datetime_obj = db\activity::get_min_datetime( $day_activity_mod );
          $max_datetime_obj = db\activity::get_max_datetime( $day_activity_mod );

          $contents[] = array(
            //sprintf( '%s.%s.',
            //         substr( $db_user->first_name, 0, 1 ),
            //         substr( $db_user->last_name, 0, 1 ) ),
            $db_user->name,
            $completes,
            is_null( $min_datetime_obj ) ? '??' : $min_datetime_obj->format( "H:i" ),
            is_null( $max_datetime_obj ) ? '??' : $max_datetime_obj->format( "H:i" ),
            sprintf( '%0.2f', $total_time ),
            $total_time > 0 ? sprintf( '%0.2f', $completes / $total_time ) : '',
            $completes > 0 ? sprintf( '%0.2f', $interview_time / $completes / 60 ) : '',
            $total_time > 0 ? sprintf( '%0.2f', $calls / $total_time ) : '' );
        }
        else
        {
          $contents[] = array(
            //sprintf( '%s.%s.',
            //         substr( $db_user->first_name, 0, 1 ),
            //         substr( $db_user->last_name, 0, 1 ) ),
            $db_user->name,
            $completes,
            sprintf( '%0.2f', $total_time ),
            $total_time > 0 ? sprintf( '%0.2f', $completes / $total_time ) : '',
            $completes > 0 ? sprintf( '%0.2f', $interview_time / $completes / 60 ) : '',
            $total_time > 0 ? sprintf( '%0.2f', $calls / $total_time ) : '' );
        }

        $grand_total_completes += $completes;
        $grand_total_time += $total_time;
        $grand_total_calls += $calls;
      }

      $average_callPH = $grand_total_time > 0 ? 
        sprintf( '%0.2f', $grand_total_calls / $grand_total_time ) : 'N/A';
      $average_compPH = $grand_total_time > 0 ? 
        sprintf( '%0.2f', $grand_total_completes / $grand_total_time ) : 'N/A';

      if( $single_date )
      {
        $header = array(
          "Operator",
          "Completes",
          "Start Time",
          "End Time",
          "Total Time",
          "CompPH",
          "Avg. Length",
          "CallPH" );

        $footer = array(
          "Total",
          "sum()",
          "--",
          "--",
          "sum()",
          $average_compPH,
          "average()",
          $average_callPH );
      }
      else
      {
        $header = array(
          "Operator",
          "Completes",
          "Total Time",
          "CompPH",
          "Avg. Length",
          "CallPH" );

        $footer = array(
          "Total",
          "sum()",
          "sum()",
          $average_compPH,
          "average()",
          $average_callPH );
      }

      $title = 0 == $restrict_site_id ? $db_site->name : NULL;
      $this->add_table( $title, $header, $contents, $footer );
    }

    return parent::finish();
  }
}
?>
