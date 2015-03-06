<?php
/**
 * queue_state_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget queue_state view
 */
class queue_state_view extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'queue_state', 'view', $args );
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

    $this->add_item( 'queue_id', 'hidden' );
    $this->add_item( 'site_id', 'enum', 'Site' );
    $this->add_item( 'qnaire_id', 'enum', 'Questionnaire' );
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

    $site_class_name = lib::get_class_name( 'database\site' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // create enum arrays
    $sites = array();
    $site_mod = lib::create( 'database\modifier' );
    $site_mod->where( 'application_id', '=', lib::create( 'business\session' )->get_application()->id );
    $site_mod->order( 'name' );
    foreach( $site_class_name::select( $site_mod ) as $db_site )
      $sites[$db_site->id] = $db_site->name;
    
    $qnaires = array();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->order( 'rank' );
    foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
      $qnaires[$db_qnaire->id] = $db_qnaire->name;

    // set the view's items
    $this->set_item( 'queue_id', $this->get_record()->queue_id );
    $this->set_item( 'site_id', $this->get_record()->site_id, true, $sites );
    $this->set_item( 'qnaire_id', $this->get_record()->qnaire_id, true, $qnaires );
  }
}
