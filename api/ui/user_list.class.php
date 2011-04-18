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
class user_list extends site_restricted_list
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
    
    $session = \sabretooth\business\session::self();

    $this->add_column( 'name', 'string', 'Username', true );
    $this->add_column( 'active', 'boolean', 'Active', true );
    $this->add_column( 'role', 'string', 'Role', false );
    $this->add_column( 'last_activity', 'fuzzy', 'Last activity', false );
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
      $last = is_null( $db_activity ) ? null : $db_activity->date;

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'active' => $record->active,
               'role' => $role,
               'last_activity' => $last ) );
    }

    $this->finish_setting_rows();
  }
  
  /**
   * Overrides the parent class method since the record count depends on the site restriction
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
    }

    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
    }

    return parent::determine_record_list( $modifier );
  }
}
?>
