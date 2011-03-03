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
    
    // create enum arrays
    $languages = \sabretooth\database\participant::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $statuses = \sabretooth\database\participant::get_enum_values( 'status' );
    $statuses = array_combine( $statuses, $statuses );
    $sites = array();
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;

    // define all columns defining this record
    $this->item['first_name'] =
      array( 'heading' => 'First Name',
             'type' => 'string',
             'required' => true,
             'value' => '' );
    $this->item['last_name'] =
      array( 'heading' => 'Last Name',
             'type' => 'string',
             'required' => true,
             'value' => '' );
    $this->item['language'] =
      array( 'heading' => 'Language',
             'type' => 'enum',
             'required' => true,
             'enum' => $languages,
             'value' => current( $languages ) );
    $this->item['hin'] =
      array( 'heading' => 'Health Insurance Number',
             'type' => 'string',
             'value' => '' );
    $this->item['status'] =
      array( 'heading' => 'Condition',
             'type' => 'enum',
             'enum' => $statuses,
             'value' => '' );
    $this->item['site_id'] =
      array( 'heading' => 'Prefered Site',
             'type' => 'enum',
             'enum' => $sites,
             'value' => '' );
  }
}
?>
