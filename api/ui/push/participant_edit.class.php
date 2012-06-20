<?php
/**
 * participant_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant edit
 *
 * Edit a participant.
 * @package sabretooth\ui
 */
class participant_edit extends \cenozo\ui\push\base_edit
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
    $columns = $this->get_argument( 'columns' );

    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the participant id with a unique key
    $db_participant = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // if set, replace the source id with a unique key
    if( array_key_exists( 'source_id', $columns ) && $columns['source_id'] )
    {
      $db_source = lib::create( 'database\source', $columns['source_id'] );
      unset( $args['columns']['source_id'] );
      // we only include half of the unique key since the other half is added above
      $args['noid']['source.name'] = $db_source->name;
    }

    // if set, replace the site id with a unique key
    if( array_key_exists( 'site_id', $columns ) && $columns['site_id'] )
    {
      $db_site = lib::create( 'database\site', $columns['site_id'] );
      unset( $args['columns']['site_id'] );
      $args['noid']['site.name'] = $db_site->name;
      $args['noid']['site.cohort'] = 'tracking';
    }

    parent::finish();

    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'participant', 'edit', $args );
  }
}
?>
