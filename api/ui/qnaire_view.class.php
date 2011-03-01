<?php
/**
 * qnaire_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    $this->item['name'] =
      array( 'heading' => 'Name',
             'type' => 'string',
             'value' => $this->get_record()->name );
    $this->item['samples'] = 
      array( 'heading' => 'Number of samples',
             'type' => 'constant',
             // add a space to get around a bug in twig
             'value' => ' '.$this->get_record()->get_sample_count() );
    $this->item['description'] =
      array( 'heading' => 'Description',
             'type' => 'text',
             'value' => $this->get_record()->description );

    // create the sample sub-list widget
    $this->sample_list = new sample_list( $args );
    $this->sample_list->set_parent( $this );
    $this->sample_list->set_heading( 'Samples this questionnaire has been assigned to' );
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

    $this->sample_list->finish();
    $this->set_variable( 'sample_list', $this->sample_list->get_variables() );
  }
  
  /**
   * The qnaire list widget.
   * @var sample_list
   * @access protected
   */
  protected $sample_list = NULL;
}
?>
