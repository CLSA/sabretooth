<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // create tracking events for the new qnaire
    $db_qnaire = $this->get_leaf_record();
    $db_script = lib::create( 'database\script', $db_qnaire->script_id );

    $db_first_attempt_event_type = lib::create( 'database\event_type' );

    $db_first_attempt_event_type->name = sprintf( 'first attempt (%s)', $db_script->name );
    $db_first_attempt_event_type->description =
      sprintf( 'First attempt to contact (for the %s interview).', $db_script->name );
    $db_first_attempt_event_type->save();
    $db_qnaire->first_attempt_event_type_id = $db_first_attempt_event_type->id;

    $db_reached_event_type = lib::create( 'database\event_type' );
    $db_reached_event_type->name = sprintf( 'reached (%s)', $db_script->name );
    $db_reached_event_type->description =
      sprintf( 'The participant was first reached (for the %s interview).', $db_script->name );
    $db_reached_event_type->save();
    $db_qnaire->reached_event_type_id = $db_reached_event_type->id;
  }
}
