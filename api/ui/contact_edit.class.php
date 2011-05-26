<?php
/**
 * contact_edit.class.php
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
 * action contact edit
 *
 * Edit a contact.
 * @package sabretooth\ui
 */
class contact_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'contact', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $columns = $this->get_argument( 'columns' );

    // if there is a phone number, validate it
    if( array_key_exists( 'phone', $columns ) )
    {
      if( 10 != strlen( preg_replace( '/[^0-9]/', '', $columns['phone'] ) ) )
        throw new exc\notice(
          'Phone number must have exactly 10 digits.', __METHOD__ );
    }

    parent::execute();
  }
}
?>
