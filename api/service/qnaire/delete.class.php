<?php
/**
 * delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class delete extends \cenozo\service\delete
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // make note of the event_types so we can delete it after the qnaire is deleted
    $db_qnaire = $this->get_leaf_record();
    $this->db_started_event_type = $db_qnaire->get_started_event_type();
    $this->db_finished_event_type = $db_qnaire->get_finished_event_type();
  }

  /**
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    // delete the associated event types
    try
    {
      $this->db_started_event_type->delete();
      $this->db_finished_event_type->delete();
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->set_data( $e->get_notice() );
      $this->status->set_code( 406 );
    }
    catch( \cenozo\exception\database $e )
    {
      if( $e->is_constrained() )
      {
        $this->set_data( $e->get_failed_constraint_table() );
        $this->status->set_code( 409 );
      }
      else
      {
        $this->status->set_code( 500 );
        throw $e;
      }
    }
  }

  /**
   * Record cache
   * @var database\event_type
   * @access protected
   */
  protected $db_started_event_type = NULL;

  /**
   * Record cache
   * @var database\event_type
   * @access protected
   */
  protected $db_finished_event_type = NULL;
}
