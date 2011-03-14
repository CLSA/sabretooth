<?php
/**
 * availability_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action availability edit
 *
 * Edit a availability.
 * @package sabretooth\ui
 */
class availability_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    // TODO: manage start/end times
    //       If start time moves, move end time.
    //       If end time moves, make sure it's after start time
    //       Find other instances of start/end times and apply there as well
    $columns = $this->get_argument( 'columns', array() );
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
    
    parent::execute();
  }
}
?>
