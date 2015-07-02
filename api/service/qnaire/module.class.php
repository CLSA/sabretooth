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

    // add the total number of phases
    if( $select->has_column( 'phase_count' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'phase' );
      $join_sel->add_column( 'qnaire_id' );
      $join_sel->add_column( 'COUNT( * )', 'phase_count', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->group( 'qnaire_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS qnaire_join_phase', $join_sel->get_sql(), $join_mod->get_sql() ),
        'qnaire.id',
        'qnaire_join_phase.qnaire_id' );
      $select->add_column( 'IFNULL( phase_count, 0 )', 'phase_count', false );
    }
  }
}
