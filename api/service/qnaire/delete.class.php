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

    // delete the associated phases
    try
    {
      foreach( $this->get_leaf_record()->get_phase_object_list() as $db_phase )
        $db_phase->delete();
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->data = $e->get_notice();
      $this->status->set_code( 406 );
    }
    catch( \cenozo\exception\database $e )
    {
      if( $e->is_constrained() )
      {
        $this->data = $e->get_failed_constraint_table();
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
   * Extends parent method
   */
  protected function finish()
  {
    parent::finish();

    // delete the associated event types
    $db_qnaire = $this->get_leaf_record();
    $db_first_attempt_event_type = $db_qnaire->get_first_attempt_event_type();
    $db_reached_event_type = $db_qnaire->get_reached_event_type();
    $db_completed_event_type = $db_qnaire->get_completed_event_type();

    try
    {
      $db_first_attempt_event_type->delete();
      $db_reached_event_type->delete();
      $db_completed_event_type->delete();
    }
    catch( \cenozo\exception\notice $e )
    {
      $this->data = $e->get_notice();
      $this->status->set_code( 406 );
    }
    catch( \cenozo\exception\database $e )
    {
      if( $e->is_constrained() )
      {
        $this->data = $e->get_failed_constraint_table();
        $this->status->set_code( 409 );
      }
      else
      {
        $this->status->set_code( 500 );
        throw $e;
      }
    }
  }
}
