<?php
/**
 * role_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action role edit
 *
 * Edit a role.
 * @package sabretooth\ui
 */
class role_edit extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'role', 'edit', $args );
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->record = new $class_name( $this->get_argument( 'id' ) );
    if( is_null( $this->record ) ) throw new \sabretooth\exception\argument( 'id' );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    foreach( $this->get_argument( 'columns', array() ) as $column => $value )
    {
      $this->record->$column = $value;
    }
    // TODO: need to catch db exceptions and handle them (set invalid values, etc)
    $this->record->save();
  }
  
  /**
   * An active record of the item being viewed.
   * @var active_record
   * @access protected
   */

  protected $record = NULL;
}
?>
