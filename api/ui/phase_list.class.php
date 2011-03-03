<?php
/**
 * phase_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget phase list
 * 
 * @package sabretooth\ui
 */
class phase_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the phase list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phase', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'survey', 'Survey', false );
    $this->add_column( 'stage', 'Stage', true );
    $this->add_column( 'repeated', 'Repeated', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    // this widget must have a parent, and it must be a qnaire
    if( NULL == $this->parent || 'sabretooth\\ui\\qnaire_view' != get_class( $this->parent ) )
      throw new \sabretooth\exception\runtime(
        'Phase list must have qnaire_view as a parent.', __METHOD );
    
    // create a list of all surveys
    $surveys = \sabretooth\database\limesurvey\surveys::select();
    $survey_list = array();
    foreach( $surveys as $db_survey ) $survey_list[$db_survey->sid] = $db_survey->get_title();

    $this->set_variable( "surveys", $survey_list );
    $this->set_variable( "num_stages", $this->parent->get_record()->get_phase_count() );

    foreach( $this->get_record_list() as $record )
    {
      // get the survey
      $db_survey = new \sabretooth\database\limesurvey\surveys( $record->sid );

      $this->add_row( $record->id,
        array( 'survey' => $db_survey->get_title(),
               'stage' => $record->stage,
               'repeated' => $record->repeated ? 'Yes' : 'No' ) );
    }

    $this->finish_setting_rows();
  }
}
?>
