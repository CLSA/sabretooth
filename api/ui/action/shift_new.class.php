<?php
/**
 * shift_new.class.php
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
 * action shift new
 *
 * Create a new shift.
 * @package sabretooth\ui
 */
class shift_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    // make sure the date column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'date', $columns ) || 0 == strlen( $columns['date'] ) )
      throw new exc\notice( 'The date cannot be left blank.', __METHOD__ );
    
    $exceptions = array();

    // execute for every selected user
    foreach( $this->get_argument( 'user_id_list' ) as $user_id )
    {
      $this->get_record()->user_id = $user_id;
      try
      {
        // the UI provides date, start time and end time, need to convert to start_datetime
        // and end_datetime
        $columns = $this->get_argument( 'columns', array() );
        
        if( strtotime( $columns['start_time'] ) >= strtotime( $columns['end_time'] ) )
        {
          throw new exc\notice(
            sprintf( 'Start and end times (%s to %s) are not valid.',
                     $columns['start_time'],
                     $columns['end_time'] ),
            __METHOD__ );
        }
        
        $this->get_record()->start_datetime = $columns['date'].' '.$columns['start_time'];
        $this->get_record()->end_datetime = $columns['date'].' '.$columns['end_time'];
        
        foreach( $columns as $column => $value )
        {
          if( 'date' != $column && 'start_time' != $column && 'end_time' != $column )
            $this->get_record()->$column = $value;
        }
        $this->get_record()->save();
      }
      catch( exc\base_exception $e )
      {
        $exceptions[] = $e;
      }

      // create a new shift record for the next iteration
      $this->set_record( new db\shift() );
    }

    // throw an exception if any were caught
    if( 1 == count( $exceptions ) )
    {
      // test the exception type to decide what type of exception to throw
      $e = current( $exceptions );
      throw RUNTIME_SHIFT__SAVE_ERROR_NUMBER == $e->get_number() ?
        new exc\notice( $e, __METHOD__, $e ) : $e;
    }
    else if( 1 < count( $exceptions ) )
    {
      $message = "The following errors have occured:<br>\n";
      foreach( $exceptions as $e )
      {
        // if we find an unexpected exception throw it instead of a notice
        if( RUNTIME_SHIFT__SAVE_ERROR_NUMBER != $e->get_number() ) throw $e;
        $message .= $e->get_raw_message()."<br>\n";
      }
      throw new exc\notice( $message, __METHOD__ );
    }
  }
}
?>
