<?php
/**
 * daily_shift_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Daily shift report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class daily_shift_report extends base_report
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

  public function finish()
  {
    // get the current user's role, if it isnt a supervisor then bailout 

    $db_role = bus\session::self()->get_role();

    if( 'supervisor' != $db_role->name )
    {
      log::err( 'Only supervisors can generate their report' );
    //  $this->add_title('for supervisors only you are a'.$db_role->name );
    }

    $db_current_user = bus\session::self()->get_user();
    $db_current_site = bus\session::self()->get_site();
    
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
    
    $user_mod = new db\modifier();
    $user_mod->where( 'site_id', '=', $db_current_site->id );
    foreach( db\user::select( $user_mod ) as $db_user )
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
      $activity_mod = new db\modifier();
      $activity_mod->where( 'user_id', '=', $db_user->id );
      $activity_mod->where( 'site_id', '=', $db_current_site->id );

      // get the min and max datetimes for this day
      $activity_mod->where( 'datetime', '>=',
        $datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $activity_mod->where( 'datetime', '<=',
        $datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

      $start_datetime_obj = db\activity::get_min_datetime( $activity_mod );
      $end_datetime_obj = db\activity::get_max_datetime( $activity_mod );
          
      // if there is activity
      if( is_null( $start_datetime_obj ) || is_null( $end_datetime_obj ) ) continue;
      
      $assignment_mod = new db\modifier();
      $assignment_mod->where( 'start_datetime', '>=',
        $datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $assignment_mod->where( 'start_datetime', '<=',
        $datetime_obj->format( 'Y-m-d' ).' 23:59:59' );

      $num_user_completes = 0; 
      foreach( $db_user->get_assignment_list( $assignment_mod ) as $db_assignment )
      {
        $db_interview = $db_assignment->get_interview();
        if( $db_interview->completed ) $num_user_completes++;

      // TODO do operators only work in one language?
      // what if the interview is with a proxy and their language is different?
        $language = $db_interview->get_participant()->language;
      }
      //TODO ask if user completes should be a column in the operators table
      $total_completes += $num_user_completes;

    }
    // dummy test data
    $contents_operator_en[] = array(
      'Annette','9:30','3:15','5','0','0','5','4 completes');
    $contents_operator_en[] = array(
      'Lise','10:00','2:30','4.25','0','0','4.25','2 completes, .25 unpaid break');

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

    return parent::finish();
  }
}
?>
