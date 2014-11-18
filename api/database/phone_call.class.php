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
class phone_call extends \cenozo\database\has_note
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

// define the join to the interview table
$participant_mod = lib::create( 'database\modifier' );
$participant_mod->join(
  'assignment',
  'phone_call.assignment_id',
  'assignment.id' );
$participant_mod->join(
  'interview',
  'assignment.interview_id',
  'interview.id' );
$participant_mod->join(
  'participant',
  'interview.participant_id',
  'participant.id' );
phone_call::customize_join( 'participant', $participant_mod );
