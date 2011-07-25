<?php
/**
 * package_required.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget package required report
 * 
 * @package sabretooth\ui
 */
class package_required_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'package_required', $args );
    $this->restrict_by_site();

    $this->set_variable( 'description',
      'This report lists all participants who require a new package to be mailed to '.
      'them.  The report generates the participant\'s name, address, phone number and last '.
      'time they were successfully contacted.' );
  }

  /**
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->finish_setting_parameters();
  }
}
?>
