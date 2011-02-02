<?php
/**
 * user_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * 
 * 
 * @package sabretooth\ui
 */
class user_list extends base_list
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
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
    $this->viewable =  true;
    $this->editable =  true;
    $this->removable =  true;
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

  protected function set_rows( $limit_count, $limit_offset )
  {
    // reset the array
    $this->rows = array();
    
    // get all users for admins, site users for anyone else
    $session = \sabretooth\session::self();
    $sort = 'name' == $this->sort_column ? 'name' : NULL;
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

      array_push( $this->rows, 
        array( 'id' => $db_user->id,
               'columns' => array( $db_user->name, $role, 'TODO' ) ) );
    }
  }
  
  /**
   * The list of users to display.
   * @var array
   * @access protected
   */
  protected $users;
}
?>
