<?php
/**
 * call_attempts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget call attempts report
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_restriction( 'site' );
    $this->add_restriction( 'qnaire' );
    $this->add_restriction( 'source' );

    $this->set_variable( 'description',
      'This report lists all participants who have been assigned at least once and have not '.
      'completed the given interview.  The report includes the participant\'s UID, date of the '.
      'last time they were called and by which operator and the total number of times they '.
      'have been called.' );
  }
}
?>
