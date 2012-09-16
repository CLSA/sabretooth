<?php
/**
 * availability.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * availability: record
 */
class availability extends \cenozo\database\record {}

// define the join to the participant_site table
$participant_site_mod = lib::create( 'database\modifier' );
$participant_site_mod->where(
  'availability.participant_id', '=', 'participant_site.participant_id', false );
availability::customize_join( 'participant_site', $participant_site_mod );

// define the join to the interview table
$interview_mod = lib::create( 'database\modifier' );
$interview_mod->where(
  'availability.participant_id', '=', 'interview.participant_id', false );
availability::customize_join( 'interview', $interview_mod );
?>
