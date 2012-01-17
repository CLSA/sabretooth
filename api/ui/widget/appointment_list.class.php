<?php
/**
 * appointment_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget appointment list
 * 
 * @package sabretooth\ui
 */
class appointment_list extends \cenozo\ui\widget\site_restricted_list
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
    $this->add_column( 'phone', 'string', 'Phone number', false );
    $this->add_column( 'datetime', 'datetime', 'Date', true );
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
    // don't add appointments if this list isn't parented
    if( is_null( $this->parent ) ) $this->addable = false;
    else // don't add appointments if the parent already has an unassigned appointment
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'participant_id', '=', $this->parent->get_record()->id );
      $modifier->where( 'assignment_id', '=', NULL );
      $this->addable = 0 == db\appointment::count( $modifier );
    }

    parent::finish();

    foreach( $this->get_record_list() as $record )
    {
      $db_phone = $record->get_phone();
      $phone = is_null( $db_phone )
             ? 'not specified'
             : sprintf( '(%d) %s: %s',
                        $db_phone->rank,
                        $db_phone->type,
                        $db_phone->number );
      $this->add_row( $record->id,
        array( 'participant.first_name' => $record->get_participant()->first_name,
               'participant.last_name' => $record->get_participant()->last_name,
               'phone' => $phone,
               'datetime' => $record->datetime,
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
    $class_name = lib::get_class_name( 'database\appointment' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_count( $modifier )
         : $class_name::count_for_site( $this->db_restrict_site, $modifier );
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
    $class_name = lib::get_class_name( 'database\appointment' );
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_list( $modifier )
         : $class_name::select_for_site( $this->db_restrict_site, $modifier );
  }
}
?>
