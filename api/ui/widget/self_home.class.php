<?php
/**
 * self_home.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget self home
 */
class self_home extends \cenozo\ui\widget\self_home
{
  /** 
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    // if this is a supervisor then get the list of current assignments
    if( 'supervisor' == lib::create( 'business\session' )->get_role()->name )
    {
      try 
      {   
        // create the assignment sub-list widget
        $this->assignment_list = lib::create( 'ui\widget\assignment_list', $this->arguments );
        $this->assignment_list->set_parent( $this );
        $this->assignment_list->set_heading( 'Open Assignments' );
      }   
      catch( \cenozo\exception\permission $e )
      {   
        $this->assignment_list = NULL;
      }
    }
  }

  /**
   * Defines all items in the view.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    if( !is_null( $this->assignment_list ) )
    {
      try
      {
        $this->assignment_list->process();
        $this->assignment_list->remove_column( 'site.name' );
        $this->assignment_list->remove_column( 'end_time' );
        $this->assignment_list->remove_column( 'status' );
        $this->assignment_list->execute();
        $this->set_variable( 'assignment_list', $this->assignment_list->get_variables() );
      }
      catch( \cenozo\exception\permission $e ) {}
    }
  }

  /**
   * Overrides the assignment list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @assignment protected
   */
  public function determine_assignment_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    return $this->assignment_list->determine_record_count( $modifier );
  }

  /**
   * Overrides the assignment list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @assignment protected
   */
  public function determine_assignment_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'end_datetime', '=', NULL );
    return $this->assignment_list->determine_record_list( $modifier );
  }

  /**
   * The participant list widget.
   * @var assignment_list
   * @access protected
   */
  protected $assignment_list = NULL;
}
?>
