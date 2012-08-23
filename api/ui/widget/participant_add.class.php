<?php
/**
 * participant_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant add
 */
class participant_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'participant', 'add', $args );
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
    
    // define all columns defining this record
    $this->add_item( 'active', 'boolean', 'Active' );
    $this->add_item( 'uid', 'string', 'Unique ID' );
    $this->add_item( 'source_id', 'enum', 'Source' );
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Preferred Language' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'site_id', 'enum', 'Prefered Site' );
    $this->add_item( 'prior_contact_date', 'date', 'Prior Contact Date' );
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

    $participant_class_name = lib::get_class_name( 'database\participant' );
    $site_class_name = lib::get_class_name( 'database\site' );
    
    // create enum arrays
    $sources = array();
    $source_class_name = lib::get_class_name( 'database\source' );
    foreach( $source_class_name::select() as $db_source )
      $sources[$db_source->id] = $db_source->name;
    $languages = $participant_class_name::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = $participant_class_name::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->order( 'name' );
    foreach( $site_class_name::select( $site_mod ) as $db_site )
      $sites[$db_site->id] = $db_site->name;

    // set the view's items
    $this->set_item( 'active', true, true );
    $this->set_item( 'uid', '', false );
    $this->set_item( 'source_id', key( $sources ), false, $sources );
    $this->set_item( 'first_name', '', true );
    $this->set_item( 'last_name', '', true );
    $this->set_item( 'language', key( $languages ), false, $languages );
    $this->set_item( 'status', '', false, $statuses );
    $this->set_item( 'site_id', '', false, $sites );
    $this->set_item( 'prior_contact_date', '' );
  }
}
?>
