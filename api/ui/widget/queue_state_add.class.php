<?php
/**
 * queue_state_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget queue_state add
 */
class queue_state_add extends \cenozo\ui\widget\base_view
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
    parent::__construct( 'queue_state', 'add', $args );
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
    
    // specify in the heading which queue this queue_state belongs to
    $this->set_heading( 'Add a new queue restriction' );

    $this->add_item( 'queue_id', 'hidden' );
    $this->add_item(
      'site_id', 
      lib::create( 'business\session' )->get_role()->all_sites ? 'enum' : 'hidden',
      'Site' );
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

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();

    $site_class_name = lib::get_class_name( 'database\site' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // create enum arrays
    if( $db_role->all_sites )
    {
      $sites = array();
      $site_mod = lib::create( 'database\modifier' );
      $site_mod->where( 'service_id', '=', $session->get_service()->id );
      $site_mod->order( 'name' );
      foreach( $site_class_name::select( $site_mod ) as $db_site )
        $sites[$db_site->id] = $db_site->name;
    }
    
    $qnaires = array();
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->order( 'name' );
    foreach( $qnaire_class_name::select( $qnaire_mod ) as $db_qnaire )
      $qnaires[$db_qnaire->id] = $db_qnaire->name;

    // set the view's items
    $this->set_item( 'queue_id', $this->parent->get_record()->id );
    if( $db_role->all_sites ) $this->set_item( 'site_id', key( $sites ), true, $sites, true );
    else $this->set_item( 'site_id', $session->get_site()->id );
    $this->set_item( 'qnaire_id', key( $qnaires ), true, $qnaires, true );
  }
}
