<?php
/**
 * queue_restriction_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: queue_restriction new
 *
 * Create a new queue_restriction.
 */
class queue_restriction_new extends \cenozo\ui\push\base_new
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

    // make that at least one of columns is not null
    $columns = $this->get_argument( 'columns' );
    if( !$columns['site_id'] &&
        !$columns['city'] &&
        !$columns['region_id'] &&
        !$columns['postcode'] )
    {
      throw lib::create( 'exception\notice', 'At least one item must be specified.', __METHOD__ );
    }

    // make sure the postcode is valid
    if( $columns['postcode'] )
    {
      if( !preg_match( '/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $columns['postcode'] ) &&
          !preg_match( '/^[0-9]{5}$/', $columns['postcode'] ) )
        throw lib::create( 'exception\notice',
          'Postal codes must be in "A1A 1A1" format, zip codes in "01234" format.', __METHOD__ );
    }
  }
}
?>
