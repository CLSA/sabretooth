<?php
/**
 * demographics_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget demographics report
 * 
 * @package sabretooth\ui
 */
class demographics_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'demographics', $args );

    $this->restrict_by_site();
    
    $this->set_variable( 'description',
      'This report lists participant demographics.  The report can be moderated by site, '.
      'questionnaire, province and consent status.' );

    // add parameters to the report
    $this->add_parameter( 'qnaire_id', 'enum', 'Questionnaire' );
    $this->add_parameter( 'consent_type', 'enum', 'Consent Status' );
    $this->add_parameter( 'province_id', 'enum', 'Province' );
  }

  /**
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire ) $qnaires[$db_qnaire->id] = $db_qnaire->name;
    
    $this->set_parameter( 'qnaire_id', current( $qnaires ), true, $qnaires );

    $consent_types = db\consent::get_enum_values( 'event' );
    array_unshift( $consent_types, 'Any' );
    $consent_types = array_combine( $consent_types, $consent_types );

    $this->set_parameter( 'consent_type', current( $consent_types ), true, $consent_types );

    $region_mod = new db\modifier();
    $region_mod->order( 'abbreviation' );
    $region_mod->where( 'country', '=', 'Canada' );
    $region_types = array( 'All provinces' );
    foreach( db\region::select($region_mod) as $db_region )
      $region_types[ $db_region->id ] = $db_region->name;
      
    $this->set_parameter( 'province_id', current( $region_types ), true, $region_types );  
  
    $this->finish_setting_parameters();
  }
}
?>
