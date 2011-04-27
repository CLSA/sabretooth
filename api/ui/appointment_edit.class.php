<?php
/**
 * appointment_edit.class.php
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
 * action appointment edit
 *
 * Edit a appointment.
 * @package sabretooth\ui
 */
class appointment_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }
  
  /**
   * Overrides the parent method to check for appointment slot availability.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function execute()
  {
    // make sure there is a slot available for the appointment
    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'date', $columns ) )
    {
      $this->get_record()->date = $columns['date'];
      if( !$this->get_record()->validate_date() )
        throw new exc\notice( 'There are no operators available during that time.', __METHOD__ );
    }
    
    // no errors, go ahead and make the change
    parent::execute();
  }
}
?>
