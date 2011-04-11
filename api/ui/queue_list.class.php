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
    
    $this->add_column( 'rank', 'number', 'Rank', true );
    $this->add_column( 'enabled', 'boolean', 'Enabled', false );
    $this->add_column( 'participant_count', 'number', 'Participants', false );
    $this->add_column( 'description', 'text', 'Description', true, 'left' );
    $session = \sabretooth\business\session::self();
    if( 'supervisor' == $session->get_role()->name )
      $this->set_heading( 'Queue list for '.$session->get_site()->name );
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
    
    $session = \sabretooth\business\session::self();
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

    $qnaires = array();
    foreach( \sabretooth\database\qnaire::select() as $db_qnaire )
      $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $this->set_variable( 'qnaires', $qnaires );
    
    $restrict_qnaire_id = $this->get_argument( "restrict_qnaire_id", 0 );
    $this->set_variable( 'restrict_qnaire_id', $restrict_qnaire_id );
    $db_restrict_qnaire = $restrict_qnaire_id
                        ? new \sabretooth\database\qnaire( $restrict_qnaire_id )
                        : NULL;

    foreach( $this->get_record_list() as $record )
    {
      // restrict to the current site if the current user is a supervisor
      if( $is_supervisor ) $record->set_site( $session->get_site() );
      else if( !is_null( $db_restrict_site ) ) $record->set_site( $db_restrict_site );
      
      // restrict to the current qnaire
      $record->set_qnaire( $db_restrict_qnaire );

      $db_setting = \sabretooth\database\setting::get_setting( 'queue state', $record->name );
      $this->add_row( $record->id,
        array( 'rank' => $record->rank,
               'enabled' => 'true' == $db_setting->value,
               'participant_count' => $record->get_participant_count(),
               // I hate to put html here, but the alternative is to implement code in the
               // parent class for this ONLY instance of where we need this functionality.
               'description' => '<div class="title">'.$record->title.'</div>'.
                                '<div>'.$record->description.'</div>' ) );
    }

    $this->finish_setting_rows();
  }
  
  /**
   * Overrides the parent class method to only include ranked queues
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'rank', '!=', NULL );
    $modifier->order( 'rank' );

    return parent::determine_record_count( $modifier );
  }
  
  /**
   * Overrides the parent class method since the record list depends on the active role.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'rank', '!=', NULL );
    $modifier->order( 'rank' );

    return parent::determine_record_list( $modifier );
  }
}
?>
