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
 * user.list widget
 * 
 * @package sabretooth\ui
 */
class user_list extends base_list
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
    $this->heading =  'User list for '.( $is_admin ? 'all sites' : $session->get_site()->name );
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  true; // TODO: should be based on role
    $this->removable =  true; // TODO: should be based on role

    $this->columns = array(
      array( 'id' => 'name',
             'name' => 'username',
             'sortable' => true ),
      array( 'id' => 'role',
             'name' => 'role',
             'sortable' => false ),
      array( 'id' => 'last',
             'name' => 'last activity',
             'sortable' => true ) ); 
  }
  
  /**
   * Overrides the parent class method since the list can be sorted by a column outside of the user
   * table.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access protected
   */
  protected function determine_record_sort_column( $sort_name )
  {
    if( 'last' == $sort_name )
    { // column in activity, see user::select() for details
      $sort = 'activity.date';
    }
    else
    {
      $sort = parent::determine_record_sort_column( $sort_name );
    }

    return $sort;
  }

  /**
   * Overrides the parent class method since the record count depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access protected
   */
  protected function determine_record_count()
  {
    // only show users for current site if user is not an administrator
    $session = \sabretooth\session::self();
    return 'administrator' == $session->get_role()->name
           ? \sabretooth\database\user::count()
           : $session->get_site()->get_user_count();
  }
  
  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  protected function determine_record_list( $modifier )
  {
    // only show users for current site if user is not an administrator
    $session = \sabretooth\session::self();
    return 'administrator' == $session->get_role()->name
           ? parent::determine_record_list( $modifier )
           : $session->get_site()->get_user_list( $modifier );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_rows()
  {
    // reset the array
    $this->rows = array();
    
    foreach( $this->get_record_list() as $record )
    {
      // determine the role
      $role = 'none';
      $db_roles = $record->get_role_list();
      if( 1 == count( $db_roles ) ) $role = $db_roles[0]->name; // only one roll?
      else if( 1 < count( $db_roles ) ) $role = 'multiple'; // multiple roles?
      
      // determine the last activity
      $db_activity = $record->get_last_activity();
      $last = is_null( $db_activity )
            ? 'never'
            : \sabretooth\util::get_fuzzy_time_ago( $db_activity->date );
      
      // assemble the row for this record
      array_push( $this->rows, 
        array( 'id' => $record->id,
               'columns' => array( $record->name, $role, $last ) ) );
    }
  }
}
?>
