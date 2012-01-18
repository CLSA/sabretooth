<?php
/**
 * queue_restriction_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget queue_restriction add
 * 
 * @package sabretooth\ui
 */
class queue_restriction_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'queue_restriction', 'add', $args );
    
    // define all columns defining this record

    $type = 3 == lib::create( 'business\session' )->get_role()->tier ? 'enum' : 'hidden';
    $this->add_item( 'site_id', $type, 'Site' );
    $this->add_item( 'city', 'string', 'City' );
    $this->add_item( 'region_id', 'enum', 'Region' );
    $this->add_item( 'postcode', 'string', 'Postcode' );
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
    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;
    
    // create enum arrays
    if( $is_top_tier )
    {
      $sites = array();
      $site_class_name = lib::get_class_name( 'database\site' );
      foreach( $site_class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    }
    $regions = array();
    $region_class_name = lib::get_class_name( 'database\region' );
    foreach( $region_class_name::select() as $db_region ) $regions[$db_region->id] = $db_region->name;

    // set the view's items
    $this->set_item(
      'site_id', $session->get_site()->id, false, $is_top_tier ? $sites : NULL );
    $this->set_item( 'city', null, false );
    $this->set_item( 'region_id', null, false, $regions );
    $this->set_item( 'postcode', null, false );

    $this->finish_setting_items();
  }
}
?>
