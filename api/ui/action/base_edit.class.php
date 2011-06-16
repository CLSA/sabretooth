<?php
/**
 * base_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\action;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

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
    
    // check for time range validity, if necessary
    if( array_key_exists( 'start_time', $columns ) ||
        array_key_exists( 'end_time', $columns ) )
    {
      $start_value = array_key_exists( 'start_time', $columns )
                   ? $columns['start_time']
                   : substr( $this->get_record()->start_time, 0, -3 );
      $end_value = array_key_exists( 'end_time', $columns )
                 ? $columns['end_time']
                 : substr( $this->get_record()->end_time, 0, -3 );

      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      {
        throw new exc\notice(
          sprintf( 'Start and end times (%s to %s) are not valid.',
                   $start_value,
                   $end_value ),
          __METHOD__ );
      }   
    } 
    else if( array_key_exists( 'start_datetime', $columns ) ||
             array_key_exists( 'end_datetime', $columns ) )
    {
      $start_value = array_key_exists( 'start_datetime', $columns )
                   ? $columns['start_datetime']
                   : substr( $this->get_record()->start_datetime, 0, -3 );
      $end_value = array_key_exists( 'end_datetime', $columns )
                 ? $columns['end_datetime']
                 : substr( $this->get_record()->end_datetime, 0, -3 );

      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      {
        throw new exc\notice(
          sprintf( 'Start and end date-times (%s to %s) are not valid.',
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
    catch( exc\database $e )
    { // help describe exceptions to the user
      if( $e->is_duplicate_entry() )
      {
        reset( $columns );
        throw new exc\notice(
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
