<?php
/**
 * participant_edit.class.php
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
 * push: participant edit
 *
 * Edit a participant.
 * @package sabretooth\ui
 */
class participant_edit extends base_edit
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
  }
  
  /**
   * Extends the base action by sending the same request to Mastodon
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the participant id with a unique key
    $db_participant = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    parent::finish();

    // now send the same request to mastodon (unless we are setting the site)
    if( !array_key_exists( 'site_id', $args['columns'] ) )
    {
      $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
      $mastodon_manager->push( 'participant', 'edit', $args );
    }
  }
}
?>
