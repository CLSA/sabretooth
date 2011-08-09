<?php
/**
 * appointment_new.class.php
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
 * push: appointment new
 *
 * Create a new appointment.
 * @package sabretooth\ui
 */
class appointment_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
  }

  /**
   * Overrides the parent method to make sure the datetime isn't blank and that check for
   * appointment slot availability.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure the datetime column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'datetime', $columns ) || 0 == strlen( $columns['datetime'] ) )
      throw new exc\notice( 'The date/time cannot be left blank.', __METHOD__ );
    
    // make sure there is a slot available for the appointment
    $columns = $this->get_argument( 'columns', array() );
    
    foreach( $columns as $column => $value ) $this->get_record()->$column = $value;
    
    $force = $this->get_argument( 'force' );
    
    if( !$force && !$this->get_record()->validate_date() )
      throw new exc\notice( 'There are no operators available during that time.', __METHOD__ );
    
    // no errors, go ahead and make the change
    parent::finish();
  }
}
?>
