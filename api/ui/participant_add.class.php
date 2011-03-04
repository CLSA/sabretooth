<?php
/**
 * participant_add.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget participant add
 * 
 * @package sabretooth\ui
 */
class participant_add extends base_view
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'add', $args );
    
    // define all columns defining this record
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Language' );
    $this->add_item( 'hin', 'string', 'Health Insurance Number' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'site_id', 'enum', 'Prefered Site' );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // create enum arrays
    $languages = \sabretooth\database\participant::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = \sabretooth\database\participant::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;

    // set the view's items
    $this->set_item( 'first_name', '', true );
    $this->set_item( 'last_name', '', true );
    $this->set_item( 'language', current( $languages ), $languages, true );
    $this->set_item( 'hin', '' );
    $this->set_item( 'status', '', $statuses );
    $this->set_item( 'site_id', '', $sites );

    $this->finish_setting_items();
  }
}
?>
