<?php
/**
 * participant_tree.class.php
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
class participant_tree_report extends base_report
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
    parent::__construct( 'participant_tree', $args );
    $this->restrict_by_site();

    $this->set_variable( 'description',
      'This report lists the participant tree: where in the calling queue all participants '.
      'currently belong.' );

    // add parameters to the report
    $this->add_parameter( 'qnaire_id', 'enum', 'Questionnaire' );
  }

  /**
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire ) $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $this->set_parameter( 'qnaire_id', current( $qnaires ), true, $qnaires );

    $this->finish_setting_parameters();
  }
}
?>
