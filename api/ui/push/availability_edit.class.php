<?php
/**
 * availability_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: availability edit
 *
 * Edit a availability.
 * @package sabretooth\ui
 */
class availability_edit extends \cenozo\ui\push\base_edit
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
   * Overrides the parent method to make sure the postcode is valid.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the availability id with a unique key
    $db_availability = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_availability->get_participant()->uid;
    $args['noid']['availability.monday'] = $db_availability->monday;
    $args['noid']['availability.tuesday'] = $db_availability->tuesday;
    $args['noid']['availability.wednesday'] = $db_availability->wednesday;
    $args['noid']['availability.thursday'] = $db_availability->thursday;
    $args['noid']['availability.friday'] = $db_availability->friday;
    $args['noid']['availability.saturday'] = $db_availability->saturday;
    $args['noid']['availability.sunday'] = $db_availability->sunday;
    $args['noid']['availability.start_time'] = $db_availability->start_time;
    $args['noid']['availability.end_time'] = $db_availability->end_time;

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'availability', 'edit', $args );
  }
}
?>
