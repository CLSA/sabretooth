<?php
/**
 * participant_delete_appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action participant delete_appointment
 * 
 * @package sabretooth\ui
 */
class participant_delete_appointment extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'appointment', $args );
  }
}
?>
