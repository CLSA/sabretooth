<?php
/**
 * quota_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget quota view
 */
class quota_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'quota', 'view', $args );
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

    // create an associative array with everything we want to display about the quota
    $this->add_item( 'site_id', 'constant', 'Site' );
    $this->add_item( 'region_id', 'constant', 'Region' );
    $this->add_item( 'gender', 'constant', 'Gender' );
    $this->add_item( 'age_group_id', 'constant', 'Age Group' );
    $this->add_item( 'population', 'constant', 'Population' );
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

    $db_age_group = $this->get_record()->get_age_group();
    $age_group = sprintf( '%d to %d', $db_age_group->lower, $db_age_group->upper );

    // set the view's items
    $this->set_item( 'site_id', $this->get_record()->get_site()->name );
    $this->set_item( 'region_id', $this->get_record()->get_region()->name );
    $this->set_item( 'gender', $this->get_record()->gender );
    $this->set_item( 'age_group_id', $age_group );
    $this->set_item( 'population', $this->get_record()->population );
    $this->set_item( 'disabled', $this->get_record()->disabled, true );
  }
}
