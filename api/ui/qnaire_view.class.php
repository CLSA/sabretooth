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
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'prev_qnaire_id', 'enum', 'Previous Questionnaire',
      'The questionnaire which must be finished before this one begins.' );
    $this->add_item( 'delay', 'number', 'Delay (weeks)',
      'How many weeks after the previous questionnaire is completed before this one may begin.' );
    $this->add_item( 'skip', 'boolean', 'Skip',
      'Whether or not this questionnaire may be skipped if the next is due.' );
    $this->add_item( 'phases', 'constant', 'Number of phases' );
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

    // create enum arrays
    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire )
      if( $db_qnaire->id != $this->get_record()->id )
        $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $num_ranks = db\qnaire::count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_ranks + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'rank', $this->get_record()->rank, true, $ranks );
    $this->set_item( 'prev_qnaire_id', $this->get_record()->prev_qnaire_id, false, $qnaires );
    $this->set_item( 'delay', $this->get_record()->delay, true );
    $this->set_item( 'skip', $this->get_record()->skip, true );
    $this->set_item( 'phases', $this->get_record()->get_phase_count() );
    $this->set_item( 'description', $this->get_record()->description );

    $this->finish_setting_items();
    
    // finish the child widgets
    if( !is_null( $this->phase_list ) )
    {
      $this->phase_list->finish();
      $this->set_variable( 'phase_list', $this->phase_list->get_variables() );
    }
  }
  
  /**
   * The qnaire list widget.
   * @var phase_list
   * @access protected
   */
  protected $phase_list = NULL;
}
?>
