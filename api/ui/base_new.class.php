<?php
/**
 * base_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * Base class for all "new" actions.
 * 
 * Abstract class which defines base functionality for all "new" actions.
 * @package sabretooth\ui
 */
class base_new extends action
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
    parent::__construct( $subject, 'new', $args );
    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->record = new $class_name();
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
    $this->record->save();
  }
  
  /**
   * The active record of the item being created.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
