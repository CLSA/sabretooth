<?php
/**
 * contact_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget contact list
 * 
 * @package sabretooth\ui
 */
class contact_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the contact list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'contact', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'active', 'Active', true );
    $this->add_column( 'rank', 'Rank', true );
    $this->add_column( 'type', 'Type', true );
    $this->add_column( 'details', 'Details', false );

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
      // get the details (phone or address)
      $details = $record->phone
               ? $record->phone
               : $record->city.', '.$record->get_province()->abbreviation.' '.$record->postcode;

      $this->add_row( $record->id,
        array( 'active' => $record->active ? 'Yes' : 'No',
               'rank' => $record->rank,
               'type' => $record->type,
               'details' => $details ) );
    }

    $this->finish_setting_rows();
  }
}
?>
