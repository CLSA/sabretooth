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
 * Base class for all record "edit" actions.
 * 
 * @package sabretooth\ui
 */
abstract class base_edit extends base_record_action
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

    // make sure we have an id (we don't actually need to use it since the parent does)
    $this->get_argument( 'id' );
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
      $this->get_record()->$column = $value;
    }
    
    try
    {
      $this->get_record()->save();
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
          __METHOD__, $e );
      }

      throw $e;
    }
  }
}
?>
