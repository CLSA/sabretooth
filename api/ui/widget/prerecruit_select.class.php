<?php
/**
 * prerecruit_select.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget prerecruit select
 */
class prerecruit_select extends \cenozo\ui\widget
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
    parent::__construct( 'prerecruit', 'select', $args );
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

    $this->show_heading( false );
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

    $region_site_class_name = lib::get_class_name( 'database\region_site' );
    $quota_class_name = lib::get_class_name( 'database\quota' );
    $prerecruit_class_name = lib::get_class_name( 'database\prerecruit' );

    $session = lib::create( 'business\session' );
    $db_assignment = $session->get_current_assignment();
    if( is_null( $db_assignment ) )
      throw lib::create( 'exception\notice',
        'You cannot run pre-recruit selection while not in an assignment.', __METHOD__ );
    $db_participant = $db_assignment->get_interview()->get_participant();

    // get a list of all quotas for the current participant's region
    $quota_mod = lib::create( 'database\modifier' );
    $quota_mod->where( 'quota.site_id', '=', $db_participant->get_default_site()->id );
    $quota_mod->where( 'quota.region_id', '=', $db_participant->get_primary_address()->region_id );
    $quota_mod->order( 'age_group.lower' );
    $quota_mod->order( 'quota.gender' );

    $selection = 'none';
    $selected_index = 0;
    $total = 0;
    $quota_list = array();
    foreach( $quota_class_name::select( $quota_mod ) as $db_quota )
    {
      $db_age_group = $db_quota->get_age_group();
      $db_prerecruit = $prerecruit_class_name::get_unique_record(
        array( 'participant_id', 'quota_id' ),
        array( $db_participant->id, $db_quota->id ) );
      $quota_list[] = array(
        'id' => $db_quota->id,
        'gender' => $db_quota->gender,
        'age_group' => sprintf( '%d to %d', $db_age_group->lower, $db_age_group->upper - 1 ),
        'total' => is_null( $db_prerecruit ) ? 0 : $db_prerecruit->total );

      if( !is_null( $db_prerecruit ) && 0 < $db_prerecruit->selected )
      {
        $selection =
          sprintf( '%s %d to %d', $db_quota->gender, $db_age_group->lower, $db_age_group->upper - 1 );
        $selected_index = $db_prerecruit->selected;
        $total = $db_prerecruit->total;
      }
    }

    $identifier = 0 == $total ? '' : $this->get_argument( 'identifier', '' );

    $this->set_variable( 'participant_id', $db_participant->id );
    $this->set_variable( 'quota_list', $quota_list );
    $this->set_variable( 'selection', $selection );
    $this->set_variable( 'selected_index', $selected_index );
    $this->set_variable( 'total', $total );
    $this->set_variable( 'identifier', $identifier );
  }
}
