<?php
/**
 * shift_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget shift list
 * 
 * @package sabretooth\ui
 */
class shift_list extends base_list_widget
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
    
    $session = \sabretooth\session::self();
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    $is_operator = 'operator' == $session->get_role()->name;
    
    if( $is_operator || $is_supervisor ) $this->set_heading( 'Shift Schedule' );

    if( !$is_operator ) $this->add_column( 'user.name', 'User', true );
    if( !$is_supervisor ) $this->add_column( 'site.name', 'Site', true );
    $this->add_column( 'date', 'Date', true );
    $this->add_column( 'start_time', 'Start', true );
    $this->add_column( 'end_time', 'End', true );
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
      $start_time = \sabretooth\util::get_formatted_time( $record->start_time, false );
      $end_time = \sabretooth\util::get_formatted_time( $record->end_time, false );
      $date = \sabretooth\util::get_formatted_date( $record->date );

      $this->add_row( $record->id,
        array( 'site.name' => $record->get_site()->name,
               'user.name' => $record->get_user()->name,
               'date' => $date,
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
    // only show users for current site if user is a supervisor
    $session = \sabretooth\session::self();
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    $is_operator = 'operator' == $session->get_role()->name;

    if( $is_operator )
    {    
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'user_id', '=', $session->get_user()->id );
    }
    else if( $is_supervisor )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', $session->get_site()->id );
    }

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
    // only show users for current site if user is a supervisor
    $session = \sabretooth\session::self();
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    $is_operator = 'operator' == $session->get_role()->name;

    if( $is_operator )
    {    
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'user_id', '=', $session->get_user()->id );
    }
    else if( $is_supervisor )
    {
      if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
      $modifier->where( 'site_id', '=', $session->get_site()->id );
    }

    return parent::determine_record_list( $modifier );
  }
}
?>
