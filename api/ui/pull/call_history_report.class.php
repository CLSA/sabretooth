<?php
/**
 * call_history.class.php
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
 * Consent form report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class call_history_report extends base_report
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
    parent::__construct( 'call_history', $args );
  }

  public function finish()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
      
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

    $assignment_mod = new db\modifier();
    $assignment_mod->order( 'start_datetime' );
    if( $restrict_start_date && $restrict_end_date )
    {
      $assignment_mod->where( 'start_datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
      $assignment_mod->where( 'end_datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
    }
    else if( $restrict_start_date && !$restrict_end_date ) 
    {
      $assignment_mod->where( 'start_datetime', '>=',
        $start_datetime_obj->format( 'Y-m-d' ).' 0:00:00' );
    }
    else if( !$restrict_start_date && $restrict_end_date )
    {
      $assignment_mod->where( 'start_datetime', '<=',
        $end_datetime_obj->format( 'Y-m-d' ).' 23:59:59' );
    }
    
    $contents = array();
    foreach( db\assignment::select( $assignment_mod ) as $db_assignment )
    {
      $db_user = $db_assignment->get_user();
      
      $phone_call_mod = new db\modifier();
      $phone_call_mod->order( 'start_datetime' );
      foreach( $db_assignment->get_phone_call_list( $phone_call_mod ) as $db_phone_call )
      {
        $contents[] = array(
          $db_assignment->get_site()->name,
          $db_assignment->get_interview()->get_participant()->uid,
          $db_user->first_name.' '.$db_user->last_name,
          $db_assignment->id,
          substr( $db_assignment->start_datetime,
                  0,
                  strpos( $db_assignment->start_datetime, ' ' ) ),
          substr( $db_phone_call->start_datetime,
                  strpos( $db_phone_call->start_datetime, ' ' ) + 1 ),
          substr( $db_phone_call->end_datetime,
                  strpos( $db_phone_call->end_datetime, ' ' ) + 1 ),
          $db_phone_call->status );
      }
    }
    
    $header = array(
      'Site',
      'UID',
      'Operator',
      'Assignment ID',
      'Date',
      'Call Start',
      'Call End',
      'Call Result' );
    
    // remove the site if we are restricting the report
    if( $restrict_site_id )
    {
      array_shift( $header );
      foreach( $contents as $index => $content ) array_shift( $contents[$index] );
    }

    $this->add_table( NULL, $header, $contents, NULL );

    return parent::finish();
  }
}
?>
