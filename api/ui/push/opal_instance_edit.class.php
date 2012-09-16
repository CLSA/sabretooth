<?php
/**
 * opal_instance_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: opal_instance edit
 *
 * Edit a opal_instance.
 */
class opal_instance_edit extends \cenozo\ui\push\base_edit
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
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $columns = $this->get_argument( 'columns', array() );

    // check to see if active is in the column list
    if( array_key_exists( 'active', $columns ) ) 
    {   
      $this->active = $columns['active'];
      unset( $this->arguments['columns']['active'] );
    }   
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $columns = $this->get_argument( 'columns', array() );

    if( !is_null( $this->active ) ) 
    {
      // send the request to change the active column of the instance's user through an operation
      $args = array( 'id' => $this->get_record()->get_user()->id,
                     'columns' => array( 'active' => $this->active ) );
      $db_operation = lib::create( 'ui\push\user_edit', $args );
      $db_operation->process();
    }
  }

  /**
   * The new value for the active parameter, or NULL if the active parameter is to remain
   * unchanged.
   * @var string $active
   * @access protected
   */
  protected $active = NULL;
}
?>
