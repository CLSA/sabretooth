<?php
/**
 * phone_edit.class.php
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
        throw new exc\notice(
          'Phone numbers must have exactly 10 digits.', __METHOD__ );
    }

    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the phone id with a unique key
    $db_phone = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_phone->get_participant()->uid;
    $args['noid']['phone.rank'] = $db_phone->rank;
    
    // if set, replace the address id with a unique key
    if( array_key_exists( 'address_id', $columns ) && $columns['address_id'] )
    {
      $db_address = new db\address( $columns['address_id'] );
      unset( $args['address_id'] );
      // we only include half of the unique key since the other half is added above
      $args['noid']['address.rank'] = $db_address->rank;
    }

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\cenozo_manager::self( MASTODON_URL );
    $mastodon_manager->push( 'phone', 'edit', $args );
  }
}
?>
