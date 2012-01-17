<?php
/**
 * phase_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget phase view
 * 
 * @package sabretooth\ui
 */
class phase_view extends \cenozo\ui\widget\base_view
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
    $this->add_item( 'rank', 'enum', 'Stage' );
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
    $surveys = array();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', 'Y' );
    $modifier->where( 'anonymized', '=', 'N' );
    $modifier->where( 'tokenanswerspersistence', '=', 'Y' );
    $class_name = lib::get_class_name( 'database\limesurvey\surveys' );
    foreach( $class_name::select( $modifier ) as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();
    $num_phases = $this->get_record()->get_qnaire()->get_phase_count();
    $ranks = array();
    for( $rank = 1; $rank <= $num_phases; $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );

    // set the view's items
    $this->set_item( 'sid', $this->get_record()->sid, true, $surveys );
    $this->set_item( 'rank', $this->get_record()->rank, true, $ranks );
    $this->set_item( 'repeated', $this->get_record()->repeated, true );

    $this->finish_setting_items();
  }
}
?>
