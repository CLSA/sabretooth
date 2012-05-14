<?php
/**
 * interview_rescore.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget operator assignment
 * 
 * @package sabretooth\ui
 */
class interview_rescore extends \cenozo\ui\widget\base_record
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', 'rescore', $args );
    $this->set_heading( 'Rescore Interview for '.$this->get_record()->get_participant()->uid );

    try
    {
      // create the recording sub-list widget      
      $this->recording_list = lib::create( 'ui\widget\recording_list', $args );
      $this->recording_list->set_parent( $this );
    }
    catch( \cenozo\exception\permission $e )
    {
      $this->recording_list = NULL;
    }
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $session = lib::create( 'business\session' );
    
    // finish the child widgets
    if( !is_null( $this->recording_list ) )
    {
      $this->recording_list->process();
      $this->set_variable( 'recording_list', $this->recording_list->get_variables() );
    }
  }
  
  /**
   * The interview list widget.
   * @var recording_list
   * @access protected
   */
  protected $recording_list = NULL;
}
?>
