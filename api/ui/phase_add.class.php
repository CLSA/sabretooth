<?php
/**
 * phase_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget phase add
 * 
 * @package sabretooth\ui
 */
class phase_add extends base_view
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
    parent::__construct( 'phase', 'add', $args );
    
    // add items to the view
    $this->add_item( 'qnaire_id', 'hidden' );
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
    
    // this widget must have a parent, and it must be a qnaire
    if( is_null( $this->parent ) ||
        'sabretooth\\ui\\qnaire_add_phase' != get_class( $this->parent ) )
      throw new \sabretooth\exception\runtime(
        'Phase widget must have qnaire_view as a parent.', __METHOD );
    
    // create enum arrays
    foreach( \sabretooth\database\limesurvey\surveys::select() as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();
    $num_phases = $this->parent->get_record()->get_phase_count();
    $stages = array();
    for( $stage = 1; $stage <= ( $num_phases + 1 ); $stage++ )
      array_push( $stages, $stage );
    $stages = array_combine( $stages, $stages );
    end( $stages );
    $last_stage_key = key( $stages );
    reset( $stages );

    // set the view's items
    $this->set_item( 'qnaire_id', $this->parent->get_record()->id );
    $this->set_item( 'sid', key( $surveys ), $surveys, true );
    $this->set_item( 'stage', $last_stage_key, $stages, true );
    $this->set_item( 'repeated', 'No', true );

    $this->finish_setting_items();
  }
}
?>
