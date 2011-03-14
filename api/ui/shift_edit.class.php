<?php
/**
 * shift_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action shift edit
 *
 * Edit a shift.
 * @package sabretooth\ui
 */
class shift_edit extends base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
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
    $column = $this->get_argument( 'columns', array() );

    // TODO: manage start/end times
    //       If start time moves, move end time.
    //       If end time moves, make sure it's after start time
    //       Find other instances of start/end times and apply there as well
    /*
    if( array_key_exists( 'start_time', $columns ) )
    {
      $start_value = $columns['start_time'];
      $end_value = array_key_exists( 'end_time', $columns )
                 ? $columns['end_time']
                 : $this->get_record()->end_time;

      if( strtotime( $start_value ) >= strtotime( $end_value ) )
      {
        throw new \sabretooth\exception\notice(
          sprintf( 'Make sure that the start time comes before the end time' ),
          __METHOD__ );
      }
    }
    */
    try
    {
      parent::execute();
    }
    catch( \sabretooth\exception\runtime $e )
    { // TODO: help describe exceptions to the user
      //       This is will happen in other places (appointments, for instance) so it should
      //       Maybe not live here?
      /*
      throw new \sabretooth\exception\notice(
        sprintf( 'Unable to set time from %s to %s, another shift is during that period.',
                 $TODO,
                 $TODO ), __METHOD__, $e ); */

      throw $e;
    }
  }
}
?>
