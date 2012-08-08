<?php
/**
 * appointment_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: appointment edit
 *
 * Edit a appointment.
 */
class appointment_edit extends \cenozo\ui\push\base_edit
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
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure there is a slot available for the appointment
    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'datetime', $columns ) )
    {
      $this->get_record()->datetime = $columns['datetime'];
      if( !$this->get_record()->validate_date() )
        throw lib::create( 'exception\notice',
          'There are no operators available during that time.', __METHOD__ );
    }
  }
}
?>
