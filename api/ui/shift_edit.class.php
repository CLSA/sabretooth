<?php
/**
 * shift_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action shift edit
 *
 * Edit a shift.
 * @package sabretooth\ui
 */
class shift_edit extends base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    try
    {
      parent::execute();
    }
    catch( \sabretooth\exception\runtime $e )
    { // the shift class throws a runtime exception when time conflicts occur
      throw RUNTIME_SHIFT__SAVE_ERROR_NUMBER == $e->get_number() ?
        new \sabretooth\exception\notice( $e, __METHOD__, $e ) : $e;
    }
  }
}
?>
