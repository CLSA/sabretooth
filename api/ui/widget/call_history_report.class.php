<?php
/**
 * call_history.class.php
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
 * widget call history report
 * 
 * @package sabretooth\ui
 */
class call_history_report extends base_report
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
    parent::__construct( 'call_history', $args );

    $this->add_restriction( 'site' );
    $this->add_restriction( 'dates' );

    $this->set_variable( 'description',
      'This report chronologically lists assignment call attempts.  The report includes the '.
      'participant\'s UID, operator\'s name, date of the assignment, result, start and end time '.
      'of each call.' );
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
