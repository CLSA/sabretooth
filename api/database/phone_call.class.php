<?php
/**
 * phone_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * phone_call: record
 */
class phone_call extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    if( !is_null( $this->assignment_id ) && is_null( $this->end_datetime ) )
    {
      // make sure there is a maximum of 1 unfinished call per assignment
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'assignment_id', '=', $this->assignment_id );
      $modifier->where( 'end_datetime', '=', NULL );
      if( 0 < static::count( $modifier ) )
        throw lib::create( 'exception\runtime',
          'Cannot have more than one active phone call per assignment.', __METHOD__ );
    }

    parent::save();
  }
}
