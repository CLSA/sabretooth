<?php
/**
 * disposition_report.class.php
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
class disposition_report extends site_restricted_report
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
    parent::__construct( 'disposition', $args );
    
    $this->set_variable( 'description', 'TBD' );

    // add paramters to the report
    $this->add_parameter( 'date', 'date', 'Date', 'Leave blank for an overall report.' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->set_parameter( 'date', '', false );
    
    $this->finish_setting_parameters();
  }
}
?>
