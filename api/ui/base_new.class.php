<?php
/**
 * base_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

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
    if( array_key_exists( 'start_time', $columns ) &&
        array_key_exists( 'end_time', $columns ) )
    { 
      if( strtotime( $columns['start_time'] ) >= strtotime( $columns['end_time'] ) )
      { 
        throw new exc\notice(
          sprintf( 'Start and end times (%s to %s) are not valid.',
                   $columns['start_time'],
                   $columns['end_time'] ),
          __METHOD__ );
      }
    } 
    else if( array_key_exists( 'start_datetime', $columns ) &&
             array_key_exists( 'end_datetime', $columns ) )
    { 
      if( strtotime( $columns['start_datetime'] ) >= strtotime( $columns['end_datetime'] ) )
      { 
        throw new exc\notice(
          sprintf( 'Start and end date-times (%s to %s) are not valid.',
                   $columns['start_datetime'],
                   $columns['end_datetime'] ),
          __METHOD__ );
      }
    } 
    
    // set record column values
    foreach( $columns as $column => $value ) $this->get_record()->$column = $value;

    try
    {
      $this->get_record()->save();
    }
    catch( exc\database $e )
    { // help describe exceptions to the user
      if( $e->is_duplicate_entry() )
      {
        throw new exc\notice(
          'Unable to create the new '.$this->get_subject().' because it is not unique.',
          __METHOD__, $e );
      }

      throw $e;
    }
  }
}
?>
