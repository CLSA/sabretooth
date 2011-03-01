<?php
/**
 * sample_add_qnaire.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget sample add_qnaire
 * 
 * @package sabretooth\ui
 */
class sample_add_qnaire extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the qnaire.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', 'qnaire', $args );
  }

  /**
   * Overrides the qnaire list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_qnaire_count( $modifier = NULL )
  {
    return $this->get_record()->get_qnaire_count_inverted( $modifier );
  }

  /**
   * Overrides the qnaire list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  public function determine_qnaire_list( $modifier = NULL )
  {
    return $this->get_record()->get_qnaire_list_inverted( $modifier );
  }
}
?>
