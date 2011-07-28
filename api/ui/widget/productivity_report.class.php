<?php
/**
 * productivity_report.class.php
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
 * widget productivity report
 * 
 * @package sabretooth\ui
 */
class productivity_report extends base_report
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
    parent::__construct( 'productivity', $args );

    $this->restrict_by_site();
    
    $this->set_variable( 'description',
      'This report lists operator productivity.  The report can either be generated for a '.
      'particilar day (which will include start and end times), or overall.  The report '.
      'includes the number of completed interviews, total working time calls per hour and '.
      'average interview length.' );

    // add paramters to the report
    $this->add_parameter( 'date', 'date', 'Date', 'Leave blank for an overall report.' );

    $this->add_parameter( 'qnaire_id', 'enum', 'Questionnaire' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $this->set_parameter( 'date', '', false );
    
    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire ) $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $this->set_parameter( 'qnaire_id', current( $qnaires ), true, $qnaires );
  
    $this->finish_setting_parameters();
  }
}
?>
