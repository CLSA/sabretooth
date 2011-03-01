<?php
/**
 * user_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget user list
 * 
 * @package sabretooth\ui
 */
class user_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the user list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', $args );
    
    $session = \sabretooth\session::self();
    $is_admin = 'administrator' == $session->get_role()->name;

    // define all template variables for this list
    $this->set_heading( sprintf( 'User list for %s',
                        $is_admin ? 'all sites' : $session->get_site()->name ) );

    $this->add_column( 'name', 'Username', true );
    $this->add_column( 'active', 'Active', true );
    $this->add_column( 'role', 'Role', false );
    $this->add_column( 'last_activity', 'Last activity', false );
  }
  
  /**
   * Overrides the parent class method since the record count depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    // only show users for current site if user is not an administrator
    $session = \sabretooth\session::self();
    if( 'administrator' != $session->get_role()->name )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', $session->get_site()->id );
    }

    return \sabretooth\database\user::count( $modifier );
  }
  
  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    // only show users for current site if user is not an administrator
    $session = \sabretooth\session::self();
    if( 'administrator' != $session->get_role()->name )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', $session->get_site()->id );
    }

    return \sabretooth\database\user::select( $modifier );
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
    
    foreach( $this->get_record_list() as $record )
    {
      // determine the role
      $role = 'none';
      $db_roles = $record->get_role_list();
      if( 1 == count( $db_roles ) ) $role = $db_roles[0]->name; // only one roll?
      else if( 1 < count( $db_roles ) ) $role = 'multiple'; // multiple roles?
      
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = \sabretooth\util::get_fuzzy_time_ago(
                is_null( $db_activity ) ? null : $db_activity->date );

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'active' => $record->active ? 'Yes' : 'No',
               'role' => $role,
               'last_activity' => $last ) );
    }

    $this->finish_setting_rows();
  }
}
?>
