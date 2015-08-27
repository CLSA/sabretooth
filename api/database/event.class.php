<?php
/**
 * event.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * event: record
 */
class event extends \cenozo\database\event
{
  /**
   * Overrides the parent save method.
   */
  public function save()
  {
    parent::save();

    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'event_type_id', '=', $this->event_type_id );
    if( 0 < static::db()->get_one( 'SELECT COUNT(*) FROM qnaire_has_event_type '.$modifier->get_sql() ) )
      $this->get_participant()->update_queue_status();
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = NULL;
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'event_type_id', '=', $this->event_type_id );
    if( 0 < static::db()->get_one( 'SELECT COUNT(*) FROM qnaire_has_event_type '.$modifier->get_sql() ) )
      $db_participant = $this->get_participant();
    parent::delete();
    if( !is_null( $db_participant ) ) $db_participant->update_queue_status();
  }
}
