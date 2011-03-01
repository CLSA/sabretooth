<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget participant list
 * 
 * @package sabretooth\ui
 */
class participant_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the participant list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'first_name', 'First Name', true );
    $this->add_column( 'last_name', 'Last Name', true );
    $this->add_column( 'language', 'Language', true );
    $this->add_column( 'status', 'Condition', true );
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
        array( 'first_name' => $record->first_name,
               'last_name' => $record->last_name,
               'language' => $record->language,
               'status' => $record->status ? $record->status : '(none)' ) );
    }

    $this->finish_setting_rows();
  }
}
?>
