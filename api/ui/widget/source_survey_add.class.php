<?php
/**
 * source_survey_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget source_survey add
 */
class source_survey_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'source_survey', 'add', $args );
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
    
    // add items to the view
    $this->add_item( 'phase_id', 'hidden' );
    $this->add_item( 'source_id', 'enum', 'Source' );
    $this->add_item( 'sid', 'enum', 'Survey' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    // this widget must have a parent, and it's subject must be a phase
    if( is_null( $this->parent ) || 'phase' != $this->parent->get_subject() )
      throw lib::create( 'exception\runtime',
        'Source survey widget must have a parent with phase as the subject.', __METHOD__ );

    // create enum arrays
    $sources = array();
    $source_class_name = lib::get_class_name( 'database\source' );
    foreach( $source_class_name::select() as $db_source )
      $sources[$db_source->id] = $db_source->name;

    $surveys = array();
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'active', '=', 'Y' );
    $modifier->where( 'anonymized', '=', 'N' );
    $modifier->where( 'tokenanswerspersistence', '=', 'Y' );
    $class_name = lib::get_class_name( 'database\limesurvey\surveys' );
    foreach( $class_name::select( $modifier ) as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();

    // set the view's items
    $this->set_item( 'phase_id', $this->parent->get_record()->id );
    $this->set_item( 'source_id', key( $sources ), true, $sources );
    $this->set_item( 'sid', key( $sources ), true, $surveys );
  }
}
?>
