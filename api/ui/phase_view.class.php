<?php
/**
 * phase_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget phase view
 * 
 * @package sabretooth\ui
 */
class phase_view extends base_view
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
    parent::__construct( 'phase', 'view', $args );
    
    // add items to the view
    $this->add_item( 'sid', 'enum', 'Survey' );
    $this->add_item( 'stage', 'enum', 'Stage' );
    $this->add_item( 'repeated', 'boolean', 'Repeated' );
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
    foreach( \sabretooth\database\limesurvey\surveys::select() as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();
    $stages = array();
    for( $stage = 1; $stage <= $this->get_record()->get_qnaire()->get_phase_count(); $stage++ )
      array_push( $stages, $stage );
    $stages = array_combine( $stages, $stages );

    // set the view's items
    $this->set_item( 'sid', $this->get_record()->sid, true, $surveys );
    $this->set_item( 'stage', $this->get_record()->stage, true, $stages );
    $this->set_item( 'repeated', $this->get_record()->repeated, true );

    $this->finish_setting_items();
  }
}
?>
