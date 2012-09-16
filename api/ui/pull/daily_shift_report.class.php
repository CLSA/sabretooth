<?php
/**
 * daily_shift_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Daily shift report data.
 * 
 * @abstract
 */
class daily_shift_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'daily_shift', $args );
  }

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    // get the current user's role, if it isnt a supervisor then bailout 

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    if( 'supervisor' != $db_role->name )
    {
      log::err( 'Only supervisors can generate their report' );
    //  $this->add_title('for supervisors only you are a'.$db_role->name );
    }

    $db_current_user = $session->get_user();
    $db_current_site = $session->get_site();
    
    // set the title and sub title(s)
    $title = 'Daily Shift Report';
    $title .= ' for '.$db_current_site->name;
    $this->add_title( $title );

    // the report is only generated at the end of a site supervisor's shift
    $datetime_obj = util::get_datetime_object();    
    $date_str = $datetime_obj->format( 'l, F jS, Y' );

    $this->add_title( $date_str );
    
    // a table for the supervisor

    $contents_supervisor = array(
      'Date' => $date_str,
      'Shift' => 'TBD',
      'Supervisor' => $db_current_user->name,
      'Hours' => 0 );

    // a table for the shift

    $contents_shift = array(
      'Completes' => 0,
      'Calling Hours' => 0,
      'Calling CPH' => 0,
      'Downtime Hours' => 0,
      'Training Hours' => 0,
      'Paid Breaks' => 0,
      'Shift CPH' => 0 );

    // a table for operators speaking english

    $contents_operators_en = array();

    // a table for operators speaking french

    $contents_operators_fr = array();

    $total_calling_hours_en = 0;
    $total_training_hours_en = 0;
    $total_downtime_hours_en = 0;
    $total_shift_hours_en = 0;
    $total_calling_hours_fr = 0;
    $total_training_hours_fr = 0;
    $total_downtime_hours_fr = 0;
    $total_shift_hours_fr = 0;
    $total_completes = 0; 
    
    $user_mod = lib::create( 'database\modifier' );
    $user_mod->where( 'site_id', '=', $db_current_site->id );
    $user_class_name     = lib::get_class_name( 'database\user' );
    $activity_class_name = lib::get_class_name( 'database\activity');

    foreach( $user_class_name::select( $user_mod ) as $db_user )
    {
      // is the user an operator?
      $is_operator = false;
      foreach( $db_user->get_role_list() as $db_user_role )
      {
        if( $db_user_role->name == 'operator' ) 
        {
          $is_operator = true;
          break;
        }
      }
      if( !$is_operator ) continue;

      // make sure the operator has min/max time for this date range
      $activity_mod = lib::create( 'database\modifier' );
      $activity_mod->where( 'user_id', '=', $db_user->id );
      $activity_mod->where( 'site_id', '=', $db_current_site->id );

      // get the min and max datetimes for this day
      $activity_mod->where( 'datetime', '>=',
        $datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $activity_mod->where( 'datetime', '<=',
        $datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

      $start_datetime_obj = $activity_class_name::get_min_datetime( $activity_mod );
      $end_datetime_obj   = $activity_class_name::get_max_datetime( $activity_mod );
          
      // if there is activity
      if( is_null( $start_datetime_obj ) || is_null( $end_datetime_obj ) ) continue;
      
      $assignment_mod = lib::create( 'database\modifier' );
      $assignment_mod->where( 'start_datetime', '>=',
        $datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $assignment_mod->where( 'start_datetime', '<=',
        $datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

      $num_user_completes = 0; 
      foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
      {
        $db_interview = $db_assignment->get_interview();
        if( $db_interview->completed ) $num_user_completes++;
      }
      
      //TODO ask if user completes should be a column in the operators table
      $total_completes += $num_user_completes;
    }

    $contents_shift['Completes'] = $total_completes;

    $denom = $contents_shift['Calling Hours'];
    $contents_shift['Calling CPH'] = $denum > 0 ?
                                     $contents_shift['Completes']/$denom : 'NA'; 

    $denom = 
      $contents_shift['Calling Hours'] +
      $contents_shift['Training Hours'] +
      $contents_shift['Downtime Hours'] +
      $contents_shift['Paid Breaks'];

    $contents_shift['Shift CPH'] = $denom > 0 ? 
                                   $contents_shift['Completes']/$denom : 'NA';

    $contents_supervisor = array( 
      array_keys( $contents_supervisor ), 
      array_values( $contents_supervisor ) );

    $contents_shift = array( 
      array_keys( $contents_shift ), 
      array_values( $contents_shift ) );

    $this->add_table( 'Supervisor', NULL, $contents_supervisor, NULL );
    $this->add_table( 'Shift', NULL, $contents_shift, NULL );
    $header = array(
      "Interviewer",
      "Start Time",
      "End Time",
      "Calling Hours",
      "Training Hours",
      "Downtime Hours",
      "Total Shift Hours",
      "Comments" );

    $footer_en = array(
      'Subtotal English', 
      '-', 
      '-',
      $total_calling_hours_en,
      $total_training_hours_en,
      $total_downtime_hours_en,
      $total_shift_hours_en, 
      '-' );

    $footer_fr = array(
      'Subtotal French', 
      '-', 
      '-',
      $total_calling_hours_fr,
      $total_training_hours_fr,
      $total_downtime_hours_fr,
      $total_shift_hours_fr, 
      '-' );

    $this->add_table( 'English', $header, $contents_operator_en, $footer_en );
    $this->add_table( 'French', $header, $contents_operator_fr, $footer_fr );
    
    $grand_totals = array (
      'TOTAL', 
      '-', 
      '-', 
      $total_calling_hours_en + $total_calling_hours_fr,
      $total_training_hours_en + $total_training_hours_fr,
      $total_downtime_hours_en + $total_downtime_hours_fr,
      $total_shift_hours_en + $total_shift_hours_fr, 
      '-' );

    $this->add_table( NULL, NULL, NULL, $grand_totals );

    // add in some empty space for Questions/Concerns

    $this->add_table( NULL,
      array_merge( array('Questions/Concerns'), array_fill( 0, sizeof( $header )-1, ' ' ) ),
      NULL, NULL );

    // add in some empty space for Comments

    $this->add_table( NULL,
      array_merge( array('Comments'), array_fill( 0, sizeof( $header )-1, ' ' ) ), 
      NULL, NULL );

    // add in a legend

    $legend = array( 
     array( 'Date = date of shift, not the date the report was prepared (if done on a different '.
     'day)' ),
     array( 'Shift = record the start and end times as per the schedule' ),
     array( 'Supervisor Hours = total shift time less any interviewing time' ),
     array( 'Completes = total interviews completed during the shift' ),
     array( 'Calling Hours is automatically populated; do not overwrite' ),
     array( 'Downtime Hours is automatically populated; do not overwrite' ),
     array( 'Training Hours is automatically populated; do not overwrite' ),
     array( 'Shift CPH is automatically populated; do not overwrite' ),
     array( 'Operator = record the names of the operators that were assigned the shift. '.
     'Also include the supervisor if s/he made any calls' ),
     array( 'Start Time = the time the operator actually started his/her shift' ),
     array( 'End Time = the time the operator actually ended his/her shift' ),
     array( 'Calling Hours = the total shift time less any training and/or downtime hours '.
     '(breaks included)' ),
     array( 'Training Hours = any time spent NOT on the phones or lost to downtime (incl. '.
     'briefings)' ),
     array( 'Downtime Hours = time lost due to technical issues. Note if the issues were '.
     'internal (at your site) or external (McMaster)' ),
     array( 'Total Shift Hours =  the total shift time (e.g. 3pm to 8pm = 5 hours). This is '.
     'based on actual time worked, not scheduled' ),
     array( 'Comments = anything specific to an operator, such as the number of interviews '.
     'they completed, downtime, etc.' ) );

    $this->add_table( NULL, 
      array_merge( array( 'Legend' ), array_fill( 0, sizeof( $header ) -1, ' ' ) ),
      $legend, NULL );
  }
}
?>
