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
class phone_call extends \cenozo\database\has_note {}

// define the join to the interview table
$participant_mod = lib::create( 'database\modifier' );
$participant_mod->where( 'phone_call.assignment_id', '=', 'assignment.id', false );
$participant_mod->where( 'assignment.interview_id', '=', 'interview.id', false );
$participant_mod->where( 'interview.participant_id', '=', 'participant.id', false );
phone_call::customize_join( 'participant', $participant_mod );
?>
