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
class address_edit extends base_edit
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
    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
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

    parent::finish();
  }
}
?>
