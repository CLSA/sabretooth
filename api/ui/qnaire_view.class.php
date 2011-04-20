<?php
/**
 * qnaire_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget qnaire view
 * 
 * @package sabretooth\ui
 */
class qnaire_view extends base_view
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
    parent::__construct( 'qnaire', 'view', $args );

    // create an associative array with everything we want to display about the qnaire
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'phases', 'constant', 'Number of phases' );
    $this->add_item( 'samples', 'constant', 'Number of samples' );
    $this->add_item( 'description', 'text', 'Description' );

    try
    {
      // create the phase sub-list widget
      $this->phase_list = new phase_list( $args );
      $this->phase_list->set_parent( $this );
      $this->phase_list->set_heading( 'Questionnaire phases' );
    }
    catch( exc\permission $e )
    {
      $this->phase_list = NULL;
    }

    try
    {
      // create the sample sub-list widget
      $this->sample_list = new sample_list( $args );
      $this->sample_list->set_parent( $this );
      $this->sample_list->set_heading( 'Samples this questionnaire has been assigned to' );
    }
    catch( exc\permission $e )
    {
      $this->sample_list = NULL;
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

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'phases', $this->get_record()->get_phase_count() );
    $this->set_item( 'samples', $this->get_record()->get_sample_count() );
    $this->set_item( 'description', $this->get_record()->description );

    $this->finish_setting_items();
    
    // finish the child widgets
    if( !is_null( $this->phase_list ) )
    {
      $this->phase_list->finish();
      $this->set_variable( 'phase_list', $this->phase_list->get_variables() );
    }

    if( !is_null( $this->sample_list ) )
    {
      $this->sample_list->finish();
      $this->set_variable( 'sample_list', $this->sample_list->get_variables() );
    }
  }
  
  /**
   * The qnaire list widget.
   * @var phase_list
   * @access protected
   */
  protected $phase_list = NULL;
  
  /**
   * The qnaire list widget.
   * @var sample_list
   * @access protected
   */
  protected $sample_list = NULL;
}
?>
