<?php
/**
 * productivity_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget self status
 * 
 * @package sabretooth\ui
 */
class productivity_report extends site_restricted_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'productivity', $args );
    
    $this->set_variable( 'description',
      'This report lists operator productivity.  The report can either be generated for a '.
      'particilar day (which will include start and end times), or over over a period of '.
      'time.  The report includes the number of completed interviews, total working time '.
      'calls per hour and average interview length.' );

    // add paramters to the report
    $this->add_parameter( 'type', 'enum', 'Type' );
    $this->add_parameter( 'start_date', 'date', 'Start Date',
      'Only used for "day" and "date span" report types.' );
    $this->add_parameter( 'end_date', 'date', 'End Date',
      'Only used for "date span" report types.  Leave blank if you wish the report to include '.
      'all dates up until today.' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // create enum arrays
    $types = array( 'day', 'date span', 'overall' );
    $types = array_combine( $types, $types );
    
    $this->set_parameter( 'type', key( $types ), true, $types );
    $this->set_parameter( 'start_date', util::get_datetime_object()->format( 'Y-m-d' ), false );
    $this->set_parameter( 'end_date', '', false );
    
    $this->finish_setting_parameters();
  }
}
?>
