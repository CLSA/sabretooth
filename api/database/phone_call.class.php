<?php
/**
 * phone_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * phone_call: record
 *
 * @package sabretooth\database
 */
class phone_call extends \cenozo\database\has_note
{
  /**
   * Extend the select() method by adding a custom join to the participant table.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select( $modifier = NULL, $count = false )
  {
    $participant_mod = lib::create( 'database\modifier' );
    $participant_mod->where( 'phone_call.assignment_id', '=', 'assignment.id', false );
    $participant_mod->where( 'assignment.interview_id', '=', 'interview.id', false );
    $participant_mod->where( 'interview.participant_id', '=', 'participant.id', false );

    static::customize_join( 'participant', $participant_mod );

    return parent::select( $modifier, $count );
  }
}
?>
