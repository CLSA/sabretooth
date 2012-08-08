<?php
/**
 * qnaire_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget qnaire view
 */
class qnaire_view extends \cenozo\ui\widget\base_view
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

    // create an associative array with everything we want to display about the qnaire
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'prev_qnaire_id', 'enum', 'Previous Questionnaire',
      'The questionnaire which must be finished before this one begins.' );
    $this->add_item( 'delay', 'number', 'Delay (weeks)',
      'How many weeks after the previous questionnaire is completed before this one may begin.' );
    $this->add_item( 'withdraw_sid', 'enum', 'Withdraw Survey' );
    $this->add_item( 'rescore_sid', 'enum', 'Rescore Survey' );
    $this->add_item( 'phases', 'constant', 'Number of phases' );
    $this->add_item( 'description', 'text', 'Description' );

    // create the phase sub-list widget
    $this->phase_list = lib::create( 'ui\widget\phase_list', $this->arguments );
    $this->phase_list->set_parent( $this );
    $this->phase_list->set_heading( 'Questionnaire phases' );

    // create the source_withdraw sub-list widget
    $this->source_withdraw_list = lib::create( 'ui\widget\source_withdraw_list', $this->arguments );
    $this->source_withdraw_list->set_parent( $this );
    $this->source_withdraw_list->set_heading( 'Source-specific Withdraw Surveys' );
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

    // create enum arrays
    $qnaires = array();
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    foreach( $qnaire_class_name::select() as $db_qnaire )
      if( $db_qnaire->id != $this->get_record()->id )
        $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $num_ranks = $qnaire_class_name::count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_ranks + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );

    $surveys = array();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', 'Y' );
    $modifier->where( 'anonymized', '=', 'N' );
    $modifier->where( 'tokenanswerspersistence', '=', 'Y' );
    $surveys_class_name = lib::get_class_name( 'database\limesurvey\surveys' );
    foreach( $surveys_class_name::select( $modifier ) as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();

    // set the view's items
    $this->set_item( 'name', $this->get_record()->name, true );
    $this->set_item( 'rank', $this->get_record()->rank, true, $ranks );
    $this->set_item( 'prev_qnaire_id', $this->get_record()->prev_qnaire_id, false, $qnaires );
    $this->set_item( 'delay', $this->get_record()->delay, true );
    $this->set_item( 'withdraw_sid', $this->get_record()->withdraw_sid, false, $surveys );
    $this->set_item( 'rescore_sid', $this->get_record()->rescore_sid, false, $surveys );
    $this->set_item( 'phases', $this->get_record()->get_phase_count() );
    $this->set_item( 'description', $this->get_record()->description );
    
    // process the child widgets
    $this->set_item( 'phases', $this->get_record()->get_phase_count() );
    $this->set_item( 'description', $this->get_record()->description );
    
    // process the child widgets
    try
    {
      $this->phase_list->process();
      $this->set_variable( 'phase_list', $this->phase_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
    
    // process the child widgets
    try
    {
      $this->source_withdraw_list->process();
      $this->set_variable( 'source_withdraw_list', $this->source_withdraw_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
  }
  
  /**
   * The qnaire list widget.
   * @var phase_list
   * @access protected
   */
  protected $phase_list = NULL;
  
  /**
   * The qnaire list widget.
   * @var source_withdraw_list
   * @access protected
   */
  protected $source_withdraw_list = NULL;
}
?>
