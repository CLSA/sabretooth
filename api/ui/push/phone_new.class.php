<?php
/**
 * phone_new.class.php
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
 * push: phone new
 *
 * Create a new phone.
 * @package sabretooth\ui
 */
class phone_new extends base_new
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
   * Overrides the parent method to make sure the number isn't blank and is a valid number.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    // make sure the datetime column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'number', $columns ) )
      throw new exc\notice( 'The number cannot be left blank.', __METHOD__ );

    // validate the phone number
    if( 10 != strlen( preg_replace( '/[^0-9]/', '', $columns['number'] ) ) )
      throw new exc\notice(
        'Phone numbers must have exactly 10 digits.', __METHOD__ );

    $args = $this->arguments;
    unset( $args['columns']['participant_id'] );
    unset( $args['columns']['address_id'] );

    // replace the participant id with a unique key
    $db_participant = new db\participant( $columns['participant_id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // replace the address id (if it is not null) a unique key
    if( $columns['address_id'] )
    {
      $db_address = new db\address( $columns['address_id'] );
      // this is only actually half of the key, the other half is provided by the participant above
      $args['noid']['address.rank'] = $db_address->rank;
    }

    // no errors, go ahead and make the change
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = bus\mastodon_manager::self();
    $mastodon_manager->push( 'phone', 'new', $args );
  }
}
?>
