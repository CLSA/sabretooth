<?php
/**
 * call_attempts.class.php
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
 * widget call attempts report
 * 
 * @package sabretooth\ui
 */
class call_attempts_report extends base_report
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
    parent::__construct( 'call_attempts', $args );
    $this->add_restriction( 'site' );
    $this->add_restriction( 'qnaire' );

    $this->set_variable( 'description',
      'This report lists all participants who have been assigned at least once and have not '.
      'completed the given interview.  The report includes the participant\'s UID, date of the '.
      'last time they were called and by which operator and the total number of times they '.
      'have been called.' );
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
