<?php
/**
 * base_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "delete" actions.
 * 
 * Abstract class which defines base functionality for all "delete" actions.
 * @package sabretooth\ui
 */
class base_delete extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'delete', $args );
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
    // TODO: error handling
    $this->record->delete();
  }
  
  /**
   * The active record of the item being deleted.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
