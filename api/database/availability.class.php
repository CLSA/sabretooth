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
class availability extends \cenozo\database\availability {}

// define the join to the interview table
$interview_mod = lib::create( 'database\modifier' );
$interview_mod->where(
  'availability.participant_id', '=', 'interview.participant_id', false );
availability::customize_join( 'interview', $interview_mod );
