<?php
/**
 * sourcing_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget sourcing required report
 */
class sourcing_required_report extends base_report
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
    parent::__construct( 'sourcing_required', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->add_restriction( 'site' );
    $this->add_restriction( 'qnaire' );

    $this->set_variable( 'description',
      'This report lists all participants who require sourcing. '.
      'The report generates the participant\'s id, name, address, the last '.
      'date they were successfully contacted, and the contact information '.
      'for two alternates.' );
  }
}
?>
