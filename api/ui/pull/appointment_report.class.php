<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent form report data.
 * 
 * @abstract
 */
class appointment_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'appointment', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $db_site = lib::create( 'business\session' )->get_site();
    $date = $this->get_argument( 'date' );
   
    $this->add_title(
      sprintf( 'Appointments for %s on %s',
               $db_site->name,
               util::get_formatted_date( $date ) ) );

    $contents = array();
    $header = array( 'UID', 'Time', 'Type', 'Reached', 'Operator' );
    
    $appointment_class_name = lib::get_class_name( 'database\appointment' );
    $appointment_mod = lib::create( 'database\modifier' );
    $appointment_mod->where( 'participant_site.site_id', '=', $db_site->id );
    $appointment_mod->where( 'datetime', '>=', $date.' 00:00:00' );
    $appointment_mod->where( 'datetime', '<=', $date.' 23:59:59' );
    $appointment_mod->order( 'datetime' );
    foreach( $appointment_class_name::select( $appointment_mod ) as $db_appointment )
    {
      $db_assignment = $db_appointment->get_assignment();
      $contents[] = array(
        $db_appointment->get_participant()->uid,
        util::get_formatted_time( $db_appointment->datetime, false ),
        $db_appointment->type,
        $db_appointment->reached ? 'Yes' : 'No',
        is_null( $db_assignment ) ? 'none' : $db_assignment->get_user()->name );
    }

    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
