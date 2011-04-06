<?php
/**
 * setting_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget setting view
 * 
 * @package sabretooth\ui
 */
class setting_view extends base_view
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
    parent::__construct( 'setting', 'view', $args );

    // create an associative array with everything we want to display about the setting
    $this->add_item( 'category', 'constant', 'Category' );
    $this->add_item( 'name', 'constant', 'Name' );
    $this->add_item( 'value', 'string', 'Value' );
    $this->add_item( 'description', 'text', 'Description' );
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

    // set the view's items
    $this->set_item( 'category', $this->get_record()->category, true );
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'value', $this->get_record()->value, true );
    $this->set_item( 'description', $this->get_record()->description, false );

    $this->finish_setting_items();
  }
}
?>
