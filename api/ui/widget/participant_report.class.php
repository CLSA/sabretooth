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

    $this->add_separator();
    $this->add_parameter( 'qnaire_id', 'enum', 'Questionnaire' );
    $this->add_parameter( 'qnaire_start_date', 'date', 'Available Start Date' );
    $this->add_parameter( 'qnaire_end_date', 'date', 'Available End Date' );
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
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // create the enum lists
    $call_results = array( 'any' );
    $call_results =
      array_merge( $call_results, $phone_call_class_name::get_enum_values( 'status' ) );
    $call_results = array_combine( $call_results, $call_results );
    $qnaire_list = array();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->order( 'name' );
    foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
      $qnaire_list[$db_qnaire->id] = $db_qnaire->name;

    $this->set_parameter( 'last_call_result', current( $call_results ), true, $call_results );
    $this->set_parameter( 'qnaire_id', current( $qnaire_list ), false, $qnaire_list );
    $this->set_parameter( 'qnaire_start_date', NULL, false );
    $this->set_parameter( 'qnaire_end_date', NULL, false );
  }
}
