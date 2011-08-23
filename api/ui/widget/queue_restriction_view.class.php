<?php
/**
 * queue_restriction_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget queue_restriction view
 * 
 * @package sabretooth\ui
 */
class queue_restriction_view extends base_view
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
    parent::__construct( 'queue_restriction', 'view', $args );

    // define all columns defining this record
    $this->add_item( 'site_id', 'enum', 'Site' );
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

    // create enum arrays
    $sites = array();
    foreach( db\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
    $regions = array();
    foreach( db\region::select() as $db_region ) $regions[$db_region->id] = $db_region->name;

    // set the view's items
    $this->set_item( 'site_id', $this->get_record()->site_id, false, $sites );
    $this->set_item( 'city', $this->get_record()->city, false );
    $this->set_item( 'region_id', $this->get_record()->region_id, false, $regions );
    $this->set_item( 'postcode', $this->get_record()->postcode, false );

    $this->finish_setting_items();
  }
}
?>
