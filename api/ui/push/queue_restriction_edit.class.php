<?php
/**
 * queue_restriction_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: queue_restriction edit
 *
 * Edit a queue_restriction.
 */
class queue_restriction_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue_restriction', $args );
  }

  /**
   * Validate the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    $session = lib::create( 'business\session' );

    // make sure that only top tier roles can edit queue restrictions not belonging
    // to the current site
    if( 3 != $session->get_role()->tier &&
        $session->get_site()->id != $this->get_record()->site_id )
    {
      throw lib::create( 'exception\notice',
        'You do not have access to edit this queue restriction.', __METHOD__ );
    }

    // make that at least one of columns is not null
    $columns = $this->get_argument( 'columns' );
    if( ( ( array_key_exists( 'site_id', $columns ) && !$columns['site_id'] ) ||
          is_null( $this->get_record()->site_id ) ) &&
        ( ( array_key_exists( 'city', $columns ) && !$columns['city'] ) ||
          is_null( $this->get_record()->city ) ) &&
        ( ( array_key_exists( 'region_id', $columns ) && !$columns['region_id'] ) ||
          is_null( $this->get_record()->region_id ) ) &&
        ( ( array_key_exists( 'postcode', $columns ) && !$columns['postcode'] ) ||
          is_null( $this->get_record()->postcode ) ) )
    {
      throw lib::create( 'exception\notice', 'At least one item must be specified.', __METHOD__ );
    }

    // make sure the postcode is valid
    if( array_key_exists( 'postcode', $columns ) )
    {
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $columns['postcode'] ) &&
          !preg_match( '/^[0-9]{5}$/', $columns['postcode'] ) )
        throw lib::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );
    }
  }
}
?>
