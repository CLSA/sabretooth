<?php
/**
 * activity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * activity: record
 */
class activity extends \cenozo\database\activity
{
  /**
   * Extend parent method
   */
  public static function get_expired_modifier()
  {
    $modifier = parent::get_expired_modifier();

    // exclude users who are in an assignment from the lapsed-activity modifier
    $sub_sel = lib::create( 'database\select' );
    $sub_sel->from( 'phone_call' );
    $sub_sel->add_column( 'COUNT(*)', 'total', false );
    $sub_mod = lib::create( 'database\modifier' );
    $sub_mod->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
    $sub_mod->where( 'phone_call.end_datetime', '=', NULL );
    $sub_mod->where( 'user_id', '=', 'access.user_id', false );
    $modifier->where( sprintf( '( %s %s )', $sub_sel->get_sql(), $sub_mod->get_sql() ), '=', 0 );

    return $modifier;
  }
}
