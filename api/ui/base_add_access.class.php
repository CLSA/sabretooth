<?php
/**
 * base_add_access.class.php
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
 * Base class for adding access to sites and users.
 * 
 * @package sabretooth\ui
 */
class base_add_access extends base_add_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The operation's subject.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'access', $args );
    
    try
    {
      // build the role list widget
      $this->role_list = new role_list( $args );
      $this->role_list->set_parent( $this, 'edit' );
      $this->role_list->set_heading( 'Select roles to grant' );
    }
    catch( exc\permission $e )
    {
      $this->role_list = NULL;
    }
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

    if( !is_null( $this->role_list ) )
    {
      $this->role_list->finish();
      $this->set_variable( 'role_list', $this->role_list->get_variables() );
    }
  }
  
  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_role_count( $modifier = NULL )
  {
    if( 'administrator' != bus\session::self()->get_role()->name )
    { // make sure that only admins can grant admin access
      if( is_null( $modifier ) ) $modifier = new db\modifier();
      $modifier->where( 'name', '!=', 'administrator' );
    }
    return db\role::count( $modifier );
  }

  /**
   * Overrides the role list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_role_list( $modifier = NULL )
  {
    if( 'administrator' != bus\session::self()->get_role()->name )
    { // make sure that only admins can grant admin access
      if( is_null( $modifier ) ) $modifier = new db\modifier();
      $modifier->where( 'name', '!=', 'administrator' );
    }
    return db\role::select( $modifier );
  }

  /**
   * The role list widget used to define the access type.
   * @var role_list
   * @access protected
   */
  protected $role_list = NULL;
}
?>
