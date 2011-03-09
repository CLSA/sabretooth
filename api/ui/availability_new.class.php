<?php
/**
 * availability_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action availability new
 *
 * Create a new availability.
 * @package sabretooth\ui
 */
class availability_new extends base_new
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
    // make sure start time comes before end time
    $columns = $this->get_argument( 'columns', array() );
    if( strtotime( $columns['start_time'] ) >= strtotime( $columns['end_time'] ) )
    {
      throw new \sabretooth\exception\notice(
        sprintf( 'Make sure that the start time comes before the end time' ),
        __METHOD__ );
    }

    parent::execute();
  }
}
?>
