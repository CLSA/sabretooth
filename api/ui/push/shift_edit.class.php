<?php
/**
 * shift_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: shift edit
 *
 * Edit a shift.
 * @package sabretooth\ui
 */
class shift_edit extends base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    try
    {
      // the UI provides date, start time and end time, need to convert to start_datetime
      // and end_datetime
      $columns = $this->get_argument( 'columns', array() );
      
      $date = array_key_exists( 'date', $columns )
            ? $columns['date']
            : substr( $this->get_record()->start_datetime, 0, 10 );
      $start_time = array_key_exists( 'start_time', $columns )
                  ? $columns['start_time']
                  : substr( $this->get_record()->start_datetime, 11, -3 );
      $end_time = array_key_exists( 'end_time', $columns )
                ? $columns['end_time']
                : substr( $this->get_record()->end_datetime, 11, -3 );

      if( strtotime( $start_time ) >= strtotime( $end_time ) )
      {
        throw new exc\notice(
          sprintf( 'Start and end times (%s to %s) are not valid.',
                   $start_time,
                   $end_time ),
          __METHOD__ );
      }
      
      $this->get_record()->start_datetime = $date.' '.$start_time;
      $this->get_record()->end_datetime = $date.' '.$end_time;
      
      foreach( $columns as $column => $value )
      {
        if( 'date' != $column && 'start_time' != $column && 'end_time' != $column )
          $this->get_record()->$column = $value;
      }
      $this->get_record()->save();
    }
    catch( exc\runtime $e )
    { // the shift class throws a runtime exception when time conflicts occur
      throw RUNTIME_SHIFT__SAVE_ERROR_NUMBER == $e->get_number() ?
        new exc\notice( $e, __METHOD__, $e ) : $e;
    }
  }
}
?>
