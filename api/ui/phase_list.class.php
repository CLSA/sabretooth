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
