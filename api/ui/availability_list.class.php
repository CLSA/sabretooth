<?php
/**
 * availability_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget availability list
 * 
 * @package sabretooth\ui
 */
class availability_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the availability list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'availability', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'monday', 'Mon', true );
    $this->add_column( 'tuesday', 'Tue', true );
    $this->add_column( 'wednesday', 'Wed', true );
    $this->add_column( 'thursday', 'Thu', true );
    $this->add_column( 'friday', 'Fri', true );
    $this->add_column( 'saturday', 'Sat', true );
    $this->add_column( 'sunday', 'Sun', true );
    $this->add_column( 'period_start', 'Start', true );
    $this->add_column( 'period_end', 'End', true );
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
        array( 'monday' => $record->monday ? 'Yes' : 'No',
               'tuesday' => $record->tuesday ? 'Yes' : 'No',
               'wednesday' => $record->wednesday ? 'Yes' : 'No',
               'thursday' => $record->thursday ? 'Yes' : 'No',
               'friday' => $record->friday ? 'Yes' : 'No',
               'saturday' => $record->saturday ? 'Yes' : 'No',
               'sunday' => $record->sunday ? 'Yes' : 'No',
               'period_start' => $record->period_start,
               'period_end' => $record->period_end ) );
    }

    $this->finish_setting_rows();
  }
}
?>
