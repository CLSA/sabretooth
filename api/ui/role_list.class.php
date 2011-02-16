<?php
/**
 * role_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget role list
 * 
 * @package sabretooth\ui
 */
class role_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the role list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', $args );
    
    $session = \sabretooth\session::self();

    // define all template variables for this list
    $this->set_heading( 'Role list' );

    $this->columns = array(
      array( 'id' => 'name',
             'heading' => 'name',
             'sortable' => true ),
      array( 'id' => 'users',
             'heading' => 'users',
             'sortable' => false ) );
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
      array_push( $this->rows, 
        array( 'id' => $record->id,
               'columns' => array( 'name' => $record->name,
                                   'users' => $record->get_user_count() ) ) );
    }
  }
}
?>
