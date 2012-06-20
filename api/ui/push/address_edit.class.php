<?php
/**
 * address_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: address edit
 *
 * Edit a address.
 * @package sabretooth\ui
 */
class address_edit extends \cenozo\ui\push\base_edit
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

    // validate the postcode
    if( array_key_exists( 'postcode', $columns ) )
    {
      $postcode = $columns['postcode'];
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $postcode ) && // postal code
          !preg_match( '/^[0-9]{5}$/', $postcode ) )  // zip code
        throw lib::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );

      // determine the region, timezone and daylight savings from the postcode
      $this->get_record()->postcode = $postcode;
      $this->get_record()->source_postcode();
    }

    // we'll need the arguments to send to mastodon
    $args = $this->arguments;

    // replace the address id with a unique key
    $db_address = $this->get_record();
    unset( $args['id'] );
    $args['noid']['participant.uid'] = $db_address->get_participant()->uid;
    $args['noid']['address.rank'] = $db_address->rank;
    
    // if set, replace the region id with a unique key
    if( array_key_exists( 'region_id', $columns ) && $columns['region_id'] )
    {
      $db_region = lib::create( 'database\region', $columns['region_id'] );
      unset( $args['columns']['region_id'] );
      // we only include half of the unique key since the other half is added above
      $args['noid']['region.abbreviation'] = $db_region->abbreviation;
    }

    parent::finish();

    // now send the same request to mastodon
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'address', 'edit', $args );
  }
}
?>
