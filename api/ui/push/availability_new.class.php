<?php
/**
 * availability_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: availability new
 *
 * Create a new availability.
 * @package sabretooth\ui
 */
class availability_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
  }

  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {

    $args = $this->arguments;
    unset( $args['columns']['participant_id'] );

    // replace the participant id with a unique key
    $columns = $this->get_argument( 'columns' );
    $db_participant = lib::create( 'database\participant', $columns['participant_id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // no errors, go ahead and make the change
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'availability', 'new', $args );
  }
}
?>
