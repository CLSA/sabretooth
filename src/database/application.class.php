<?php
/**
 * application.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * application: record
 */
class application extends \cenozo\database\application
{
  /**
   * Returns a special event-type associated with the application
   * 
   * Returns the event-type associated with
   * If no event-type exists this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\event_type
   * @access public
   */
  public function get_first_attempt_event_type()
  {
    if( false === $this->db_first_attempt_event_type )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );
      $this->db_first_attempt_event_type =
        $event_type_class_name::get_unique_record( 'name', sprintf( 'first attempt (%s)', INSTANCE ) );
    }
    return $this->db_first_attempt_event_type;
  }

  /**
   * Returns a special event-type associated with the application
   * 
   * Returns the event-type associated with
   * If no event-type exists this method will return NULL.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database\event_type
   * @access public
   */
  public function get_reached_event_type()
  {
    if( false === $this->db_reached_event_type )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );
      $this->db_reached_event_type =
        $event_type_class_name::get_unique_record( 'name', sprintf( 'reached (%s)', INSTANCE ) );
    }
    return $this->db_reached_event_type;
  }

  /**
   * Record cache
   * @var database\event_type
   * @access protected
   */
  protected $db_first_attempt_event_type = false;

  /**
   * Record cache
   * @var database\event_type
   * @access protected
   */
  protected $db_reached_event_type = false;
}
