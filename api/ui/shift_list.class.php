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

    $this->add_column( 'event', 'Event', true );
    $this->add_column( 'date', 'Date', true );
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
      $this->add_row( $record->id,
        array( 'event' => $record->event,
               'date' => $record->date ) );
    }

    $this->finish_setting_rows();
  }
}
?>
