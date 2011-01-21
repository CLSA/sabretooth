<?php
/**
 * main.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * main widget
 * 
 * @package sabretooth\ui
 */
class main extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function __construct()
  {
    // define all template variables for this widget
    $this->set_variable( 'survey_active', false );
  }
}
?>
