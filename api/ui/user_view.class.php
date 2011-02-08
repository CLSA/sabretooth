<?php
/**
 * user_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * user.view widget
 * 
 * @package sabretooth\ui
 */
class user_view extends base_view
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
    parent::__construct( 'user', $args );

    // define all template variables for this list
    $this->set_heading( sprintf( 'Viewing user "%s"', $this->record->name ) );
    $this->editable = true; // TODO: should be based on role
    $this->removable = true; // TODO: should be based on role
    
    // create an associative array with everything we want to display about the user
    $this->item = array( 'Username' => $this->record->name,
                         'Limesurvey username' => 'TODO' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // define all template variables for this widget
    $this->set_variable( 'id', $this->record->id );
  }
}
?>
