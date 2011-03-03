<?php
/**
 * qnaire_add_phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget qnaire add_phase
 * 
 * @package sabretooth\ui
 */
class qnaire_add_phase extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the phase.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'phase', $args );
  }

  /**
   * Overrides the phase list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_phase_count( $modifier = NULL )
  {
    return $this->get_record()->get_phase_count_inverted( $modifier );
  }

  /**
   * Overrides the phase list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_phase_list( $modifier = NULL )
  {
    return $this->get_record()->get_phase_list_inverted( $modifier );
  }
}
?>
