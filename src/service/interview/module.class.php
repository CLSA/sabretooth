<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\interview;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\interview\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( $select->has_column( 'future_appointment' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'appointment' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * ) > 0', 'future_appointment', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'datetime', '>', 'UTC_TIMESTAMP()', false );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_appointment', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_appointment.interview_id' );
      $select->add_column( 'IFNULL( future_appointment, false )', 'future_appointment', false, 'boolean' );
    }

    if( $select->has_column( 'missed_appointment' ) )
    {
      $join_sel = lib::create( 'database\select' );
      $join_sel->from( 'appointment' );
      $join_sel->add_column( 'interview_id' );
      $join_sel->add_column( 'COUNT( * ) > 0', 'missed_appointment', false );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'datetime', '<', 'UTC_TIMESTAMP()', false );
      $join_mod->where( 'assignment_id', '=', NULL );
      $join_mod->where( 'outcome', '=', NULL );
      $join_mod->group( 'interview_id' );

      $modifier->left_join(
        sprintf( '( %s %s ) AS interview_join_appointment', $join_sel->get_sql(), $join_mod->get_sql() ),
        'interview.id',
        'interview_join_appointment.interview_id' );
      $select->add_column( 'IFNULL( missed_appointment, false )', 'missed_appointment', false, 'boolean' );
    }

    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    if( $select->has_table_columns( 'script' ) )
      $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
  }
}
