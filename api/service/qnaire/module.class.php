<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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

    // join to limesurvey tables to get the survey name
    if( $select->has_table_columns( 'prev_qnaire' ) )
      $modifier->left_join( 'qnaire', 'qnaire.prev_qnaire_id', 'prev_qnaire.id', 'prev_qnaire' );

    if( $select->has_table_columns( 'prev_script' ) )
    {
      $modifier->left_join( 'qnaire', 'qnaire.prev_qnaire_id', 'prev_qnaire.id', 'prev_qnaire' );
      $modifier->left_join( 'script', 'prev_qnaire.script_id', 'prev_script.id', 'prev_script' );
    }

    if( $select->has_table_columns( 'script' ) )
      $modifier->left_join( 'script', 'qnaire.script_id', 'script.id' );
  }
}
