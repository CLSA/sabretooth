<?php
/**
 * shift_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget shift list
 * 
 * @package sabretooth\ui
 */
class shift_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the shift list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
    
    $this->user_id = $this->get_argument( 'user_id', NULL );
    $this->add_column( 'user.name', 'string', 'User', true );
    $this->add_column( 'start_time', 'number', 'Start', true );
    $this->add_column( 'end_time', 'number', 'End', true );

    if( !is_null( $this->user_id ) )
    {
      $db_user = new db\user( $this->user_id );
      $this->set_heading( 'Shift list for '.$db_user->name );
    }
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    foreach( $this->get_record_list() as $record )
    {
      $start_time = strtotime( $record->date.' '.$record->start_time ) * 1000;
      $end_time = strtotime( $record->date.' '.$record->end_time ) * 1000;
      $this->add_row( $record->id,
        array( 'user.name' => $record->get_user()->name,
               'start_time' => $start_time,
               'end_time' => $end_time ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method since the record count depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    $session = bs\session::self();

    if( NULL == $modifier ) $modifier = new db\modifier();
    if( 'operator' == $session->get_role()->name )
      $modifier->where( 'user_id', '=', $session->get_user()->id );
    else if( !is_null( $this->user_id ) )
      $modifier->where( 'user_id', '=', $this->user_id );

    return parent::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    $session = bs\session::self();

    if( NULL == $modifier ) $modifier = new db\modifier();
    if( 'operator' == $session->get_role()->name )
      $modifier->where( 'user_id', '=', $session->get_user()->id );
    else if( !is_null( $this->user_id ) )
      $modifier->where( 'user_id', '=', $this->user_id );

    return parent::determine_record_list( $modifier );
  }

  /**
   * The user to restrict the list to.
   * @var int
   * @access protected
   */
  protected $user_id = NULL;
}
?>
