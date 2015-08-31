<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\queue_state;
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

    if( $select->has_table_columns( 'qnaire' ) || $select->has_table_columns( 'script' ) )
    {
      $modifier->join( 'qnaire', 'queue_state.qnaire_id', 'qnaire.id' );
      if( $select->has_table_columns( 'script' ) )
        $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    }
  }
}
