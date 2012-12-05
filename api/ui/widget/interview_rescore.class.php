<?php
/**
 * interview_rescore.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget operator assignment
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_heading( 'Rescore Interview for '.$this->get_record()->get_participant()->uid );

    // create the recording sub-list widget
    $this->recording_list = lib::create( 'ui\widget\recording_list', $this->arguments );
    $this->recording_list->set_parent( $this );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $session = lib::create( 'business\session' );
    
    // process the child widgets
    try
    {
      $this->recording_list->process();
      $this->set_variable( 'recording_list', $this->recording_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }
  
  /**
   * The interview list widget.
   * @var recording_list
   * @access protected
   */
  protected $recording_list = NULL;
}
?>
