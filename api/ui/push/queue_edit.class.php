<?php
/**
 * queue_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: queue edit
 *
 * Create a edit queue.
 */
class queue_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $queue_state_class_name = lib::get_class_name( 'database\queue_state' );

    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'enabled', $columns ) )
    {
      $db_queue = $this->get_record();
      $site_id = $this->get_argument( 'site_id' );
      $qnaire_id = $this->get_argument( 'qnaire_id' );

      $db_queue_state = $queue_state_class_name::get_unique_record(
        array( 'queue_id', 'site_id', 'qnaire_id' ),
        array( $db_queue->id, $site_id, $qnaire_id ) );

      if( $columns['enabled'] )
      { // delete the record if it exists
        if( !is_null( $db_queue_state ) ) $db_queue_state->delete();
      }
      else
      {
        if( is_null( $db_queue_state ) )
        { // a record doesn't exist so create one
          $db_queue_state = lib::create( 'database\queue_state' );
          $db_queue_state->queue_id = $db_queue->id;
          $db_queue_state->site_id = $site_id;
          $db_queue_state->qnaire_id = $qnaire_id;
        }

        $db_queue_state->enabled = false;
        $db_queue_state->save();
      }
    }
    else parent::execute();
  }
}
