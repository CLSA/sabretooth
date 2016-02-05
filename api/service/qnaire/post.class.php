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

    $db_started_event_type = lib::create( 'database\event_type' );
    $db_started_event_type->name = sprintf( 'first attempt (%s)', $db_script->name );
    $db_started_event_type->description =
      sprintf( 'Started the "%s" questionnaire.', $db_script->name );
    $db_started_event_type->save();
    $db_qnaire->started_event_type_id = $db_started_event_type->id;

    $db_finished_event_type = lib::create( 'database\event_type' );
    $db_finished_event_type->name = sprintf( 'finished (%s)', $db_script->name );
    $db_finished_event_type->record_address = true;
    $db_finished_event_type->description =
      sprintf( 'Finished the "%" questionnaire.', $db_script->name );
    $db_finished_event_type->save();
    $db_qnaire->finished_event_type_id = $db_finished_event_type->id;

    // now repopulate the queues immediately
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::delayed_repopulate();
  }
}
