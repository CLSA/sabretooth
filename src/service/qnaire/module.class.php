<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );

    // join to the survey name
    if( $select->has_column( 'name' ) )
    {
      // add a special column "name" which is the qnaire's script's name
      if( $select->has_column( 'name' ) ) $select->add_column( 'script.name', 'name', false );
    }
  }
}
