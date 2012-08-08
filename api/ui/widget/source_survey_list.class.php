<?php
/**
 * source_survey_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget source_survey list
 */
class source_survey_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the source_survey list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'source_survey', $args );
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
    
    $this->add_column( 'source.name', 'string', 'Source', true );
    $this->add_column( 'survey', 'string', 'Survey', false );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_surveys = lib::create( 'database\limesurvey\surveys', $record->sid );

      $this->add_row( $record->id,
        array( 'source.name' => $record->get_source()->name,
               'survey' => $db_surveys->get_title() ) );
    }
  }
}
?>
