<?php
/**
 * sample_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget sample view
 * 
 * @package sabretooth\ui
 */
class sample_view extends base_view
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
    parent::__construct( 'sample', 'view', $args );

    // create an associative array with everything we want to display about the sample
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => $this->get_record()->name );
    $this->item['participants'] = 
      array( 'heading' => 'Number of participants',
             'type' => 'constant',
             // add a space to get around a bug in twig
             'value' => ' '.$this->get_record()->get_participant_count() );
    $this->item['qnaires'] = 
      array( 'heading' => 'Number of questionnaires',
             'type' => 'constant',
             // add a space to get around a bug in twig
             'value' => ' '.$this->get_record()->get_qnaire_count() );
    $this->item['description'] =
      array( 'heading' => 'Description',
             'type' => 'text',
             'value' => $this->get_record()->description );

    // create the participant sub-list widget
    $this->participant_list = new participant_list( $args );
    $this->participant_list->set_parent( $this );
    $this->participant_list->set_heading( 'Participants belonging to this sample' );

    // create the qnaire sub-list widget
    $this->qnaire_list = new qnaire_list( $args );
    $this->qnaire_list->set_parent( $this );
    $this->qnaire_list->set_heading( 'Questionnaires assigned to this sample' );
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

    $this->participant_list->finish();
    $this->set_variable( 'participant_list', $this->participant_list->get_variables() );
    $this->qnaire_list->finish();
    $this->set_variable( 'qnaire_list', $this->qnaire_list->get_variables() );
  }
  
  /**
   * The sample list widget.
   * @var participant_list
   * @access protected
   */
  protected $participant_list = NULL;

  /**
   * The sample list widget.
   * @var qnaire_list
   * @access protected
   */
  protected $qnaire_list = NULL;
}
?>
