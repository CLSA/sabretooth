<?php
/**
 * phone_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: phone edit
 *
 * Edit a phone.
 * @package sabretooth\ui
 */
class phone_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone', $args );
    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns' );

    // if there is a phone number, validate it
    if( array_key_exists( 'number', $columns ) )
    {
      if( 10 != strlen( preg_replace( '/[^0-9]/', '', $columns['number'] ) ) )
        throw lib::create( 'exception\notice',
          'Phone numbers must have exactly 10 digits.', __METHOD__ );
    }

    parent::finish();
  }
}
?>
