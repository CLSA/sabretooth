<?php
/**
 * source_survey_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget source_survey list
 * 
 * @package sabretooth\ui
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
    
    $this->add_column( 'source.name', 'string', 'Source', true );
    $this->add_column( 'survey', 'string', 'Survey', false );
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
      $db_surveys = lib::create( 'database\limesurvey\surveys', $record->sid );

      $this->add_row( $record->id,
        array( 'source.name' => $record->get_source()->name,
               'survey' => $db_surveys->get_title() ) );
    }

    $this->finish_setting_rows();
  }
}
?>
