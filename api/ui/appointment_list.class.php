<?php
/**
 * appointment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget appointment list
 * 
 * @package sabretooth\ui
 */
class appointment_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the appointment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'appointment', $args );
    
    $this->add_column( 'participant.first_name', 'string', 'First name', true );
    $this->add_column( 'participant.last_name', 'string', 'Last name', true );
    $this->add_column( 'contact', 'string', 'Contact', false );
    $this->add_column( 'date', 'datetime', 'Date', true );
    $this->add_column( 'state', 'string', 'State', false );
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
      $db_contact = $record->get_contact();
      $contact = sprintf( '(%d) %s: %s',
                          $db_contact->rank,
                          $db_contact->type,
                          $db_contact->phone );
      $this->add_row( $record->id,
        array( 'participant.first_name' => $record->get_participant()->first_name,
               'participant.last_name' => $record->get_participant()->last_name,
               'contact' => $contact,
               'date' => $record->date,
               'state' => $record->get_state() ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to restrict appointment list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_count( $modifier )
         : \sabretooth\database\appointment::count_for_site( $this->db_restrict_site, $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict appointment list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_list( $modifier )
         : \sabretooth\database\appointment::select_for_site( $this->db_restrict_site, $modifier );
  }
}
?>
