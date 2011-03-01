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

    $this->add_column( 'name', 'Name', true );
    $this->add_column( 'users', 'users', false );
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
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'users' => $record->get_user_count() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
