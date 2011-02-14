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
    $columns = $this->get_argument( 'columns', array() );
    foreach( $columns as $column => $value )
    {
      $this->record->$column = $value;
    }
    
    try
    {
      $this->record->save();
    }
    catch( \sabretooth\exception\database $e )
    { // help describe exceptions to the user
      if( $e->is_duplicate_entry() )
      {
        reset( $columns );
        throw new \sabretooth\exception\notice(
          1 == count( $columns )
          ? sprintf( 'Unable to set %s to "%s" because that value is already being used.',
                     key( $columns ),
                     current( $columns ) )
          : 'Unable to modify the '.$this->get_subject().' because it is no longer unique.',
          $e );
      }

      throw $e;
    }
  }
  
  /**
   * An active record of the item being edited.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
