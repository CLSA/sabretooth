<?php
/**
 * queue_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget queue list
 * 
 * @package sabretooth\ui
 */
class queue_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the queue list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue', $args );
    
    $this->add_column( 'name', 'string', 'Name', false );
    $this->add_column( 'enabled', 'boolean', 'Enabled', false );
    $this->add_column( 'participant_count', 'number', 'Participants', false );
    $this->add_column( 'description', 'text', 'Description', false, 'left' );
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
    
    $session = \sabretooth\session::self();
    $is_administrator = 'administrator' == $session->get_role()->name;
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    
    // if this is an admin, give them a list of sites to choose from
    if( $is_administrator )
    {
      $sites = array();
      foreach( \sabretooth\database\site::select() as $db_site )
        $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }
    
    $restrict_site_id = $this->get_argument( "restrict_site_id", 0 );
    $this->set_variable( 'restrict_site_id', $restrict_site_id );
    $db_restrict_site = $restrict_site_id
                      ? new \sabretooth\database\site( $restrict_site_id )
                      : NULL;

    foreach( $this->get_record_list() as $record )
    {
      // restrict to the current site if the current user is a supervisor
      if( $is_supervisor ) $record->set_site( $session->get_site() );
      else if( !is_null( $db_restrict_site ) ) $record->set_site( $db_restrict_site );

      $db_setting = \sabretooth\database\setting::get_setting( 'queue state', $record->name );
      $this->add_row( $record->id,
        array( 'name' => $record->name,
               'enabled' => 'true' == $db_setting->value,
               'participant_count' => $record->get_participant_count(),
               'description' => $record->description ) );
    }

    $this->finish_setting_rows();
  }
}
?>
