<?php
/**
 * event_type.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * event_type: record
 */
class event_type extends \cenozo\database\event_type
{
  /**
   * Extend parent method
   */
  public function add_qnaire( $ids )
  {
    parent::add_qnaire( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function remove_qnaire( $ids )
  {
    parent::remove_qnaire( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }

  /**
   * Extend parent method
   */
  public function replace_qnaire( $ids )
  {
    parent::replace_qnaire( $ids );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }
}
