<?php
/**
 * interview_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: interview edit
 *
 * Edit a interview.
 */
class interview_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', $args );
  }
  
  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'completed', $columns ) )
    {
      // skip the parent method
      $grand_parent = get_parent_class( get_parent_class( get_class() ) );
      $grand_parent::execute();

      // force complete the interview or throw a notice (we cannot un-complete)
      if( 1 == $columns['completed'] ) $this->get_record()->force_complete();
      else
      {
        // only allow admins to uncomplete an interview
        if( 2 < lib::create( 'business\session' )->get_role()->tier )
        {
          $this->get_record()->force_uncomplete();
        }
        else
        {
          throw lib::create( 'exception\notice',
            'Only administrators can un-complete an interview.', __METHOD__ );
        }
      }

      // now update the queue
      $this->get_record()->get_participant()->update_queue_status();
    }
    else
    {
      parent::execute();
    }
  }
}
