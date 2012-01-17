<?php
/**
 * address_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: address new
 *
 * Create a new address.
 * @package sabretooth\ui
 */
class address_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'address', $args );
  }

  /**
   * Overrides the parent method to make sure the postcode is valid.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access public
   */
  public function finish()
  {
    $columns = $this->get_argument( 'columns' );
    $postcode = $columns['postcode'];
    
    // validate the postcode
    if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $postcode ) && // postal code
        !preg_match( '/^[0-9]{5}$/', $postcode ) )  // zip code
      throw lib::create( 'exception\notice',
        'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );

    $args = $this->arguments;
    unset( $args['columns']['participant_id'] );

    // replace the participant id with a unique key
    $db_participant = lib::create( 'database\participant', $columns['participant_id'] );
    $args['noid']['participant.uid'] = $db_participant->uid;

    // replace the region id (if it is not null) a unique key
    if( $columns['region_id'] )
    {
      $db_region = lib::create( 'database\region', $columns['region_id'] );
      // this is only actually half of the key, the other half is provided by the participant above
      $args['noid']['region.abbreviation'] = $db_region->abbreviation;
    }

    // no errors, go ahead and make the change
    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'address', 'new', $args );
  }
}
?>
