<?php
/**
 * quota_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget quota add
 */
class quota_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'quota', 'add', $args );
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
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'gender', 'enum', 'Gender' );
    $this->add_item( 'age_group_id', 'enum', 'Age Group' );
    $this->add_item( 'population', 'number', 'Population' );
    $this->add_item( 'disabled', 'boolean', 'Disabled',
      'Whether participants belonging to this quota are to be removed from the queues.' );
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
    
    $region_class_name = lib::get_class_name( 'database\region' );
    $quota_class_name = lib::get_class_name( 'database\quota' );
    $age_group_class_name = lib::get_class_name( 'database\age_group' );

    // create enum arrays
    $regions = array();
    $region_mod = lib::create( 'database\modifier' );
    $region_mod->order( 'country' );
    $region_mod->order( 'name' );
    foreach( $region_class_name::select( $region_mod ) as $db_region )
      $regions[$db_region->id] = $db_region->name;
    $genders = $quota_class_name::get_enum_values( 'gender' );
    $genders = array_combine( $genders, $genders );
    $age_groups = array();
    foreach( $age_group_class_name::select() as $db_age_group )
      $age_groups[$db_age_group->id] =
        sprintf( '%d to %d', $db_age_group->lower, $db_age_group->upper );

    // set the view's items
    $this->set_item( 'region_id', key( $regions ), true, $regions );
    $this->set_item( 'gender', key( $genders ), true, $genders );
    $this->set_item( 'age_group_id', key( $age_groups ), true, $age_groups );
    $this->set_item( 'population', 0 );
    $this->set_item( 'disabled', false, true );
  }
}
?>
