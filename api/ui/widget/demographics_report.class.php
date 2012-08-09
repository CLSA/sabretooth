<?php
/**
 * demographics_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget demographics report
 */
class demographics_report extends base_report
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
    parent::__construct( 'demographics', $args );
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
    $this->add_restriction( 'source' );
    $this->add_restriction( 'consent' );
    $this->add_restriction( 'province' );
    
    $this->set_variable( 'description',
      'This report lists participant demographics.  The report can be moderated by site, '.
      'questionnaire, province and consent status.' );
  }
}
?>
