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

    $this->add_column( 'site.name', 'Site', true );
    $this->add_column( 'user.name', 'User', true );
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
}
?>
