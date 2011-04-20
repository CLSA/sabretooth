<?php
/**
 * sample_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action sample edit
 *
 * Edit a sample.
 * @package sabretooth\ui
 */
class sample_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'sample', $args );
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
    catch( exc\runtime $e )
    { // the sample class may throw a runtime exception when sample.qnaire_id is changed
      throw RUNTIME_SAMPLE__SAVE_ERROR_NUMBER == $e->get_number() ?
        new exc\notice( $e, __METHOD__, $e ) : $e;
    }
  }
}
?>
