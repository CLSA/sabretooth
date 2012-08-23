<?php
/**
 * opal_instance_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: opal_instance delete
 */
class opal_instance_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'opal_instance', $args );
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

    if( 1 < count( $this->get_record()->get_user()->get_access_count() ) )
      throw lib::create( 'exception\notice',
        'Cannot delete the opal instance since it holds more than one role.', __METHOD__ );
  }

  /**
   * Finishes the operation by deleting the access and user records.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function finish()
  {
    parent::finish();

    // finish by deleting the user and access
    $db_user = $this->get_record()->get_user();
    $db_access = current( $db_user->get_access_list() );
    if( $db_access )
    {
      $operation = lib::create( 'ui\push\access_delete', array( 'id' => $db_access->id ) );
      $operation->process();
    }
    $operation = lib::create( 'ui\push\user_delete', array( 'id' => $db_user->id ) );
    $operation->process();
  }
}
?>
