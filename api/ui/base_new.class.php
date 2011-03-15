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
    $columns = $this->get_argument( 'columns', array() );

    // check for time range validity, if necessary
    if( array_key_exists( 'start_time', $columns ) && array_key_exists( 'end_time', $columns ) )
    { 
      $start_value = $columns['start_time'];
      $end_value = $columns['end_time'];
      
      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      { 
        throw new \sabretooth\exception\notice(
          sprintf( 'Start and end times (%s to %s) are not valid.',
                   $start_value,
                   $end_value ),
          __METHOD__ );
      }
    } 
    
    // set record column values
    foreach( $columns as $column => $value ) $this->get_record()->$column = $value;

    try
    {
      $this->get_record()->save();
    }
    catch( \sabretooth\exception\database $e )
    { // help describe exceptions to the user
      if( $e->is_duplicate_entry() )
      {
        throw new \sabretooth\exception\notice(
          'Unable to create the new '.$this->get_subject().' because it is not unique.',
          __METHOD__, $e );
      }

      throw $e;
    }
  }
}
?>
