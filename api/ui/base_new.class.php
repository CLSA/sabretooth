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
 * Base class for all actions creating a new record.
 * 
 * @package sabretooth\ui
 */
abstract class base_new extends base_record_action
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
        throw new \sabretooth\exception\notice(
          'Unable to create the new '.$this->get_subject().' because it is not unique.', $e );
      }

      throw $e;
    }
  }
}
?>
