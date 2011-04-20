<?php
/**
 * shift_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget shift view
 * 
 * @package sabretooth\ui
 */
class shift_view extends base_view
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
    parent::__construct( 'shift', 'view', $args );
    
    // create an associative array with everything we want to display about the shift
    $this->add_item( 'user', 'constant', 'User' );
    // hide the site if the current user is a supervisor
    $type = 'supervisor' == bus\session::self()->get_role()->name
          ? 'hidden'
          : 'constant';
    $this->add_item( 'site', $type, 'Site' );
    $this->add_item( 'date', 'date', 'Date' );
    $this->add_item( 'start_time', 'time', 'Start Time' );
    $this->add_item( 'end_time', 'time', 'End Time' );
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
    $this->set_item( 'user', $this->get_record()->get_user()->name );
    $this->set_item( 'site', $this->get_record()->get_site()->name );
    $this->set_item( 'date', $this->get_record()->date, true );
    $this->set_item( 'start_time', $this->get_record()->start_time, true );
    $this->set_item( 'end_time', $this->get_record()->end_time, true );

    $this->finish_setting_items();
  }
}
?>
