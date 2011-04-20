<?php
/**
 * contact_list.class.php
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
    
    $this->add_column( 'active', 'boolean', 'Active', true );
    $this->add_column( 'rank', 'number', 'Rank', true );
    $this->add_column( 'type', 'string', 'Type', true );
    $this->add_column( 'details', 'string', 'Details', false );
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
    
    // only allow admins and supervisors to make direct calls
    $role_name = bus\session::self()->get_role()->name;
    $this->set_variable( 'allow_connect',
                         'administrator' == $role_name || 'supervisor' == $role_name );
    $this->set_variable( 'sip_enabled',
      bus\voip_manager::self()->get_sip_enabled() );

    foreach( $this->get_record_list() as $record )
    {
      // get the details (phone or address)
      $details = $record->phone
               ? $record->phone
               : $record->city.', '.$record->get_province()->abbreviation.' '.$record->postcode;

      $this->add_row( $record->id,
        array( 'active' => $record->active,
               'rank' => $record->rank,
               'type' => $record->type,
               'details' => $details,
               // This last item isn't actually a column, it's a hack to make sure only contacts
               // that have phone numbers in them get the call button (see tpl/contact_list.twig)
               'has_phone' => !is_null( $record->phone ) ) );
    }

    $this->finish_setting_rows();
  }
}
?>
