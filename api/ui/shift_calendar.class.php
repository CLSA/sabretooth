<?php
/**
 * shift_list.class.php
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
 * widget shift list
 * 
 * @package sabretooth\ui
 */
class shift_calendar extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', 'calendar', $args );
    
    $session = bus\session::self();

    // determine the user id
    $this->user_id = $this->get_argument( 'user_id', NULL );
    if( 'operator' == $session->get_role()->name ) $this->user_id = $session->get_user()->id;
    
    if( is_null( $this->user_id ) )
    {
      $this->set_heading( 'Shift calendar for '.$session->get_site()->name );
    }
    else
    {
      $db_user = new db\user( $this->user_id );
      $this->set_heading( 'Shift calendar for '.$db_user->name );
    }
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    $session = bus\session::self();

    // create a list of shifts
    $modifier = new db\modifier();
    if( is_null( $this->user_id ) )
      $modifier->where( 'site_id', '=', $session->get_site()->id );
    else
      $modifier->where( 'user_id', '=', $this->user_id );

    $shift_list = array();
    foreach( db\shift::select( $modifier ) as $db_shift )
    {
      $shift_list[] = array(
        'id' => $db_shift->id,
        'name' => $db_shift->get_user()->name,
        'start' => strtotime( $db_shift->date.' '.$db_shift->start_time ) * 1000,
        'end' => strtotime( $db_shift->date.' '.$db_shift->end_time ) * 1000 );
    }

    $this->set_variable( 'editable', 'operator' != $session->get_role()->name );
    $this->set_variable( 'user_id', $this->user_id );
    $this->set_variable( 'shift_list', $shift_list );
  }

  /**
   * The user to restrict the list to.
   * @var int
   * @access protected
   */
  protected $user_id = NULL;
}
?>
