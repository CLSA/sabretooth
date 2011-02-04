<?php
/**
 * user_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
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
  public function __construct( $args = NULL )
  {
    parent::__construct( 'user', $args );
    
    $session = \sabretooth\session::self();
    $is_admin = 'administrator' == $session->get_role()->name;

    // define all template variables for this list
    $this->heading =  "User list for ".( $is_admin ? 'all sites' : $session->get_site()->name );
    $this->checkable =  false;
    $this->viewable =  true; // TODO: should be based on role
    $this->editable =  true; // TODO: should be based on role
    $this->removable =  true; // TODO: should be based on role
    $this->number_of_items = 'administrator' == $session->get_role()->name
                           ? \sabretooth\database\user::count()
                           : $session->get_site()->get_user_count();

    $this->columns = array(
      array( "id" => "name",
             "name" => "username",
             "sortable" => true ),
      array( "id" => "role",
             "name" => "role",
             "sortable" => false ),
      array( "id" => "last",
             "name" => "last activity",
             "sortable" => true ) ); 
  }

  /**
   * Set the details of each user as a row.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param int $limit_count The number of rows to include.
   * @param int $limit_count The offset to start rows at.
   * @access protected
   */
  protected function set_rows( $limit_count, $limit_offset )
  {
    // reset the array
    $this->rows = array();
    
    // determine what we're sorting by
    if( 'name' == $this->sort_column )
    {
      // column in user table
      $sort = 'name';
    }
    else if( 'last' == $this->sort_column )
    {
      // column in activity, see user::select() for details
      $sort = 'activity.date';
    }
    else
    {
      $sort = NULL;
    }

    // get all users for admins, site users for anyone else
    $session = \sabretooth\session::self();
    $desc = $this->sort_desc;
    $db_user_list = 'administrator' == $session->get_role()->name
                  ? \sabretooth\database\user::select( $limit_count, $limit_offset, $sort, $desc )
                  : $session->get_site()->get_users( $limit_count, $limit_offset, $sort, $desc );
    foreach( $db_user_list as $db_user )
    {
      // determine the role
      $role = 'none';
      $db_roles = $db_user->get_roles();
      if( 1 == count( $db_roles ) ) $role = $db_roles[0]->name; // only one roll?
      else if( 1 < count( $db_roles ) ) $role = 'multiple'; // multiple roles?
      
      // determine the last activity
      $db_activity = $db_user->get_last_activity();

      $last = is_null( $db_activity )
            ? 'never'
            : \sabretooth\util::get_fuzzy_time_ago( $db_activity->date );
      array_push( $this->rows, 
        array( 'id' => $db_user->id,
               'columns' => array( $db_user->name, $role, $last ) ) );
    }
  }
}
?>
