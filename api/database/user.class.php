<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * user: record
 */
class user extends \cenozo\database\user
{
  /**
   * TODO: document
   */
  function has_open_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine if user with no id has an open assignment.' );
      return NULL;
    }

    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->where( 'end_datetime', '=', NULL );
    return 0 < $this->get_assignment_count( $assignment_mod );
  }

  /**
   * TODO: document
   */
  function get_open_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to get open assignment from user with no id.' );
      return NULL;
    }

    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->where( 'end_datetime', '=', NULL );
    $assignment_mod->order_desc( 'start_datetime' );
    $assignment_list = $this->get_assignment_object_list( $assignment_mod );
    if( 1 < count( $assignment_list ) )
      log::warning( sprintf( 'User %d (%s) has more than one open assignment!', $this->id, $this->name ) );
    return 0 < count( $assignment_list ) ? current( $assignment_list ) : NULL;
  }
}
