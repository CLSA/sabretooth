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
    
    // create enum arrays
    foreach( \sabretooth\database\limesurvey\surveys::select() as $db_survey )
      $surveys[$db_survey->sid] = $db_survey->get_title();
    $stages = array();
    for( $stage = 1; $stage <= $this->get_record()->get_qnaire()->get_phase_count(); $stage++ )
      array_push( $stages, $stage );
    $stages = array_combine( $stages, $stages );
    $sites = array( 'NULL' => '' ); // add a blank entry
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;

    // create an associative array with everything we want to display about the phase
    $this->item['sid'] =
      array( 'heading' => 'Survey',
             'type' => 'enum',
             'enum' => $surveys,
             'value' => $this->get_record()->get_survey()->get_title() );
    $this->item['stage'] =
      array( 'heading' => 'Stage',
             'type' => 'enum',
             'enum' => $stages,
             'value' => $this->get_record()->stage );
    $this->item['repeated'] =
      array( 'heading' => 'Repeated',
             'type' => 'boolean',
             'value' => $this->get_record()->repeated ? 'Yes' : 'No' );
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
  }
}
?>
