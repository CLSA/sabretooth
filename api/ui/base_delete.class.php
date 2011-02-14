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
   * @throws exception\argument
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'delete', $args );
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
    try
    {
      $this->record->delete();
    }
    catch( \sabretooth\exception\database $e )
    { // help describe exceptions to the user
      if( $e->is_constrained() )
      {
        throw new \sabretooth\exception\notice(
          'Unable to delete the '.$this->get_subject().
          ' because it is being referenced by the database.', $e );
      }

      throw $e;
    }
  }
  
  /**
   * The active record of the item being deleted.
   * @var active_record
   * @access protected
   */
  protected $record = NULL;
}
?>
