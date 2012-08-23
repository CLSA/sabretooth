<?php
/**
 * queue_restriction_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: queue_restriction delete
 */
class queue_restriction_delete extends \cenozo\ui\push\base_delete
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

    // make sure that only top tier roles can remove queue restrictions not belonging
    // to the current site
    if( 3 != $session->get_role()->tier &&
        $session->get_site()->id != $this->get_record()->site_id )
    {
      throw lib::create( 'exception\notice',
        'You do not have access to remove this queue restriction.', __METHOD__ );
    }
  }
}
?>
