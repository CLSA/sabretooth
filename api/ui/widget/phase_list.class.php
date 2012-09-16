<?php
/**
 * phase_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget phase list
 */
class phase_list extends \cenozo\ui\widget\base_list
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
    
    $this->add_column( 'survey', 'string', 'Default Survey', false );
    $this->add_column( 'rank', 'string', 'Stage', true );
    $this->add_column( 'repeated', 'boolean', 'Repeated', true );
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
      // get the survey
      $db_surveys = lib::create( 'database\limesurvey\surveys', $record->sid );

      $this->add_row( $record->id,
        array( 'survey' => $db_surveys->get_title(),
               'rank' => $record->rank,
               'repeated' => $record->repeated ) );
    }
  }
}
?>
