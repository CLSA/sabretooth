<?php
/**
 * base_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "edit" actions.
 * 
 * Abstract class which defines base functionality for all "edit" actions.
 * @package sabretooth\ui
 */
class base_edit extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param array $args Action arguments
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'edit', $args );
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->record = new $class_name( $this->get_argument( 'id' ) );
    if( is_null( $this->record ) )
      throw new \sabretooth\exception\argument( 'id', NULL, __METHOD__ );
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
    // TODO: need to catch db exceptions
    $this->record->save();
  }
  
  /**
   * An active record of the item being edited.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
