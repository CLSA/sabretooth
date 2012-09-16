<?php
/**
 * daily_shift_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget daily shift report
 */
class daily_shift_report extends base_report
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
    parent::__construct( 'daily_shift', $args );
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

    $this->set_variable( 'description',
      'This report is for supervisors to complete at the end of their shift for remittance to the '.
      'NCC on a daily basis. The report includes operator activity data with operators '.
      'subclassified by language.  Areas are provided for questions/concerns and additional '.
      'comments.' );
  }
}
?>
