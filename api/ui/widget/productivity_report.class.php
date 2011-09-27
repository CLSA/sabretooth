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
 * widget productivity report
 * 
 * @package sabretooth\ui
 */
class productivity_report extends base_report
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

    $this->add_restriction( 'site' );
    $this->add_restriction( 'qnaire' );
    $this->add_restriction( 'dates' );
    
    $this->set_variable( 'description',
      'This report lists operator productivity.  The report can either be generated for a '.
      'particilar day (which will include start and end times), or overall.  The report '.
      'includes the number of completed interviews, total working time calls per hour and '.
      'average interview length.' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
  
    $this->finish_setting_parameters();
  }
}
?>
