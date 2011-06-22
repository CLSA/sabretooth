<?php
/**
 * productivity_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\datum;
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
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'productivity', $args );
  }

  /**
   * Returns the data provided by this datum operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access public
   */
  public function get_data()
  {
    $now_datetime_obj = util::get_datetime_object();
    $report = new bus\report();
    
    // get report date (blank for overall reports)
    $daily_date = $this->get_argument( 'date' );
    if( $daily_date )
    {
      $datetime_obj = util::get_datetime_object( $daily_date );
      $today = $datetime_obj->format( 'l, F jS, Y' );
      $datetime_obj->setTime( 0, 0 );
      $start_today = $datetime_obj->format( 'Y-m-d H:i:s' );
      $datetime_obj->setTime( 23, 59, 59 );
      $end_today = $datetime_obj->format( 'Y-m-d H:i:s' );
    }

    // add the header row
    $row = 1;
    $col = 'A';
    $end_col = $daily_date ? 'G' : 'E';
    
    // add the title
    $report->merge_cells( 'A'.$row.':'.$end_col.$row );
    $title = ( $daily_date ? 'Daily' : 'Overall' ).' Productivity Report';
    $report->set_cell( $col.$row, $title, 'center', true );
    $row++;
    
    if( $daily_date )
    {
      $report->merge_cells( 'A'.$row.':'.$end_col.$row );
      $report->set_cell( $col.$row, $today, 'center' );
      $row++;
    }
    
    $report->merge_cells( 'A'.$row.':'.$end_col.$row );
    $generated = 'Generated on '.$now_datetime_obj->format( 'Y-m-d' ).
                 ' at '.$now_datetime_obj->format( 'H:i' );
    $report->set_cell( $col.$row, $generated, 'center' );

    // restrict to a site, if necessary
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $site_mod = new db\modifier();
    if( $restrict_site_id ) $site_mod->where( 'id', '=', $restrict_site_id );
    
    $db_role = db\role::get_unique_record( 'name', 'operator' );
    foreach( db\site::select( $site_mod ) as $db_site )
    {
      $row += 2;
      $col = 'A';

      // add the site header
      $report->merge_cells( 'A'.$row.':'.$end_col.$row );
      $report->set_cell( $col.$row, $db_site->name, 'center', true );
      $row++;

      $report->set_cell( $col.$row, 'Operator' );
      $report->set_fill( $col.$row, 'FFCCCCCC' );
      $col++;
      
      $report->set_cell( $col.$row, 'Completes', 'center' );
      $report->set_fill( $col.$row, 'FFCCCCCC' );
      $col++;
      
      if( $daily_date )
      {
        $report->set_cell( $col.$row, 'Start Time', 'center' );
        $report->set_fill( $col.$row, 'FFCCCCCC' );
        $col++;
      
        $report->set_cell( $col.$row, 'End Time', 'center' );
        $report->set_fill( $col.$row, 'FFCCCCCC' );
        $col++;
      }
      
      $report->set_cell( $col.$row, 'Total Time', 'center' );
      $report->set_fill( $col.$row, 'FFCCCCCC' );
      $col++;

      $report->set_cell( $col.$row, 'CPH', 'center' );
      $report->set_fill( $col.$row, 'FFCCCCCC' );
      $col++;
      
      $report->set_cell( $col.$row, 'Avg. Length', 'center' );
      $report->set_fill( $col.$row, 'FFCCCCCC' );
      $col++;
      
      $row++;

      // get all users which have the operator role
      $total_time = 0;
      $first_row = $row;
      foreach( db\user::select() as $db_user )
      {
        // make sure the operator has min/max time for this date range
        $mod = new db\modifier();
        $mod->where( 'user_id', '=', $db_user->id );
        $mod->where( 'site_id', '=', $db_site->id );
        $mod->where( 'role_id', '=', $db_role->id );
        if( $daily_date )
        {
          // get the min and max datetimes for this day
          $mod->where( 'datetime', '>=', $start_today );
          $mod->where( 'datetime', '<=', $end_today );
          
          $min_datetime_obj = db\activity::get_min_datetime( $mod );
          $max_datetime_obj = db\activity::get_max_datetime( $mod );

          if( is_object( $min_datetime_obj ) && is_object( $max_datetime_obj ) )
          {
            $diff_obj = $max_datetime_obj->diff( $min_datetime_obj );
            $total_time = $diff_obj->h + round( $diff_obj->i / 15 ) * 0.25;
          }
        }
        else
        {
          // get the overall min and max datetimes, then use those to get min/max datetimes
          // for all days
          $min_datetime_obj = db\activity::get_min_datetime( $mod );
          $max_datetime_obj = db\activity::get_max_datetime( $mod );
          
          if( is_object( $min_datetime_obj ) && is_object( $max_datetime_obj ) )
          {
            $interval = new \DateInterval( 'P1D' );
            for( $datetime_obj = clone $min_datetime_obj;
                 $datetime_obj <= $max_datetime_obj;
                 $datetime_obj->add( $interval ) )
            {
              $datetime_obj->setTime( 23, 59, 59 );
              $end_date = $datetime_obj->format( 'Y-m-d H:i:s' );
              $datetime_obj->setTime( 0, 0 );
              $start_date = $datetime_obj->format( 'Y-m-d H:i:s' );
   
              $mod = new db\modifier();
              $mod->where( 'user_id', '=', $db_user->id );
              $mod->where( 'site_id', '=', $db_site->id );
              $mod->where( 'role_id', '=', $db_role->id );
              $mod->where( 'datetime', '>=', $start_date );
              $mod->where( 'datetime', '<=', $end_date );
              
              $inner_min_datetime_obj = db\activity::get_min_datetime( $mod );
              $inner_max_datetime_obj = db\activity::get_max_datetime( $mod );
              
              if( is_object( $inner_min_datetime_obj ) && is_object( $inner_max_datetime_obj ) )
              {
                $diff_obj = $inner_max_datetime_obj->diff( $inner_min_datetime_obj );
                $total_time += $diff_obj->h + round( $diff_obj->i / 15 ) * 0.25;
              }
            }
          }
        }
        
        if( !is_null( $min_datetime_obj ) && !is_null( $max_datetime_obj ) )
        {
          $col = 'A';
  
          // name
          $report->set_cell( $col.$row, $db_user->first_name.' '.$db_user->last_name );
          $col++;
    
          // completes
          $completes = 0;
          $mod = new db\modifier();
          if( $daily_date )
          {
            $mod->where( 'start_datetime', '>=', $start_today );
            $mod->where( 'start_datetime', '<=', $end_today );
          }

          foreach( $db_user->get_assignment_list( $mod ) as $db_assignment )
            if( $db_assignment->get_interview()->completed ) $completes++;
          
          $report->set_cell( $col.$row, $completes, 'center' );
          $col++;
          
          if( $daily_date )
          {
            // start time
            $report->set_cell( $col.$row, $min_datetime_obj->format( "H:i" ), 'center' );
            $col++;
            
            // end time
            $report->set_cell( $col.$row, $max_datetime_obj->format( "H:i" ), 'center' );
            $col++;
          }

          // total time (calculated above)
          $report->set_cell( $col.$row, $total_time, 'center' );
          $col++;
    
          // completes per hour
          if( 0 < $total_time )
            $report->set_cell( $col.$row, sprintf( '%0.2f', $completes / $total_time ), 'center' );
          $col++;

          // average interview time
          $report->set_cell( $col.$row, 'TBD', 'center' );
          $col++;

          $row++;
        }
      }
      $last_row = $row - 1;
      
      $col = 'A';

      // now do the totals/sums
      $report->set_cell( $col.$row, 'Total', 'left', true );
      $col++;
      
      // completes
      $value = $first_row > $last_row ? 0 : '=SUM( '.$col.$first_row.':'.$col.$last_row.' )';
      $cell_obj = $report->set_cell( $col.$row, $value, 'center' );
      $completes = $cell_obj->getCalculatedValue();
      $col++;
      
      if( $daily_date )
      {
        // start time
        $report->set_cell( $col.$row, '--', 'center' );
        $col++;
        
        // end time
        $report->set_cell( $col.$row, '--', 'center' );
        $col++;
      }
      
      // total time
      $value = $first_row > $last_row ? 0 : '=SUM( '.$col.$first_row.':'.$col.$last_row.' )';
      $cell_obj = $report->set_cell( $col.$row, $value, 'center' );
      $total_time = $cell_obj->getCalculatedValue();
      $col++;
      

      // completes per hour
      if( 0 !== $value )
        $report->set_cell( $col.$row, sprintf( '%0.2f', $completes/$total_time ), 'center' );
      $col++;
      
      // average interview time
      $report->set_cell( $col.$row, 'TBD', 'center' );
      $col++;
      $row++;
    }

    return $report->get_file( $this->get_argument( 'format' ) );
  }
}
?>
