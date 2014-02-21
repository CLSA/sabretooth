<?php
/**
 * participant_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant report
 */
class participant_report extends \cenozo\ui\widget\participant_report
{
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

    $this->add_parameter( 'last_call_result', 'enum', 'Last Call Result' );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );

    // create the enum lists
    $call_results = array( 'any' );
    $call_results =
      array_merge( $call_results, $phone_call_class_name::get_enum_values( 'status' ) );
    $call_results = array_combine( $call_results, $call_results );

    $this->set_parameter( 'last_call_result', current( $call_results ), true, $call_results );
  }
}
