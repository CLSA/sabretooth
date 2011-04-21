<?php
/**
 * shift_calendar.class.php
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
 * widget shift calendar
 * 
 * @package sabretooth\ui
 */
class shift_calendar extends base_calendar
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
    
    $session = bus\session::self();

    // determine the user id
    $this->user_id = $this->get_argument( 'user_id', NULL );
    if( 'operator' == $session->get_role()->name ) $this->user_id = $session->get_user()->id;
    
    if( is_null( $this->user_id ) )
    {
      $this->set_heading( 'Shifts for '.$session->get_site()->name );
    }
    else
    {
      $db_user = new db\user( $this->user_id );
      $this->set_heading( 'Shifts for '.$db_user->name );
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
    
    $this->set_variable( 'allow_all_day', false );
    $this->set_variable( 'editable', 'operator' != bus\session::self()->get_role()->name );
    $this->set_variable( 'user_id', $this->user_id );
  }

  /**
   * The user to restrict the list to.
   * @var int
   * @access protected
   */
  protected $user_id = NULL;
}
?>
