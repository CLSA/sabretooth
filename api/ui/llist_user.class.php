<?php
/**
 * llist_user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * llist_user llist
 * 
 * @package sabretooth\ui
 */
class llist_user extends llist
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
    parent::__construct( $args );
    
    $operation = new \sabretooth\business\user();
    if( !$operation->has_access( 'llist' ) )
      throw new \sabretooth\exception\permission( $operation->get_db_operation( 'llist' ) );

    // define all template variables for this llist
    $this->heading =  "User list for all sites" ;
    $this->checkable =  false ;
    $this->viewable =  true ;
    $this->editable =  true ;
    $this->removable =  true ;
    $this->number_of_items = \sabretooth\database\user::count();
    $this->columns = array( "name", "role", "last activity" );
  }

  protected function set_rows( $limit_count, $limit_offset )
  {
    // reset the array
    $this->rows = array();

    $db_user_list = \sabretooth\database\user::select( $limit_count, $limit_offset );
    foreach( $db_user_list as $db_user )
    {
      // determine the role
      $role = 'none';
      
      $db_roles = $db_user->get_roles();

      if( 1 == count( $db_roles ) )
      { // only one roll?
        $role = $db_roles[0]->name;
      }
      else if( 1 < count( $db_roles ) )
      { // multiple roles
        $role = 'multiple';
      }

      array_push( $this->rows, 
        array( 'id' => $db_user->id, 'columns' => array( $db_user->name, $role, 'TODO' ) ) );
    }
  }
}
?>
