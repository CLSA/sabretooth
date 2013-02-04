<?php
/**
 * quota_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: quota new
 *
 * Create a new quota.
 */
class quota_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'quota', $args );
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

    // make sure the population column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'population', $columns ) || 0 == strlen( $columns['population'] ) )
      throw lib::create( 'exception\notice',
        'The quota\'s population cannot be left blank.', __METHOD__ );
  }
}
