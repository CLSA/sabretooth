<?php
/**
 * participant_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant new
 *
 * Create a new participant.
 * @package sabretooth\ui
 */
class participant_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
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
    // make sure the name column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'first_name', $columns ) || 0 == strlen( $columns['first_name'] ) )
      throw lib::create( 'exception\notice',
        'The participant\'s first name cannot be left blank.', __METHOD__ );
    if( !array_key_exists( 'last_name', $columns ) || 0 == strlen( $columns['last_name'] ) )
      throw lib::create( 'exception\notice',
        'The participant\'s last name cannot be left blank.', __METHOD__ );

    parent::finish();
  }
}
?>
