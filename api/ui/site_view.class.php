<?php
/**
 * site_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * site.view widget
 * 
 * @package sabretooth\ui
 */
class site_view extends base_view
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
    parent::__construct( 'site', $args );

    // make sure to validate the arguments ($args could be anything)
    if( isset( $args['id'] ) && is_numeric( $args['id'] ) )
      $this->id = $args['id'];

    // make sure we have all the arguments necessary
    if( !isset( $this->id ) )
      throw new \sabretooth\exception\argument( 'id' );

    $db_site = new \sabretooth\database\site( $this->id );

    // define all template variables for this list
    $this->heading = 'Viewing site "'.$db_site->name.'"';
    $this->editable = true; // TODO: should be based on role
    $this->removable = false;
    
    // create an associative array with everything we want to display about the site
    $this->item = array( 'Name' => $db_site->name );
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
    $this->set_variable( 'id', $this->id );
  }

  /**
   * The primary key for the site being viewed.
   * @var int
   * @access protected
   */
  protected $id = NULL;
}
?>
