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
    // skip the parent's execute() method
    $base_record_class_name = lib::get_class_name( 'ui\push\base_record' );
    $base_record_class_name::execute();

    $columns = $this->get_argument( 'columns', array() );

    if( array_key_exists( 'completed', $columns ) )
    {
      // force complete the interview or throw a notice (we cannot un-complete)
      if( 1 == $columns['completed'] )
      {
        $this->get_record()->force_complete();
      }
      else throw lib::create( 'exception\notice',
        'Interviews cannot be un-completed.', __METHOD__ );
    }
    else throw lib::create( 'exception\notice',
      'Only the "completed" state of an interview may be edited.', __METHOD__ );
  }
}
?>
