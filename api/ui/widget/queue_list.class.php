<?php
/**
 * queue_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget queue list
 * 
 * @package sabretooth\ui
 */
class queue_list extends base_list
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

    // make sure to display all queues on the same page
    $this->set_items_per_page( 1000 );
    
    $this->add_column( 'rank', 'number', 'Rank', true );
    $this->add_column( 'enabled', 'boolean', 'Enabled', false );
    $this->add_column( 'participant_count', 'number', 'Participants', false );
    $this->add_column( 'description', 'text', 'Description', true, 'left' );
    $session = bus\session::self();
    if( 'supervisor' == $session->get_role()->name )
      $this->set_heading( $this->get_heading().' for '.$session->get_site()->name );
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
    
    $session = bus\session::self();
    $is_administrator = 'administrator' == $session->get_role()->name;
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    
    // if this is an admin, give them a list of sites to choose from
    if( $is_administrator )
    {
      $sites = array();
      foreach( db\site::select() as $db_site )
        $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }

    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $this->set_variable( 'restrict_site_id', $restrict_site_id );
    $db_restrict_site = $restrict_site_id
                      ? new db\site( $restrict_site_id )
                      : NULL;

    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire )
      $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $this->set_variable( 'qnaires', $qnaires );
    
    $restrict_qnaire_id = $this->get_argument( 'restrict_qnaire_id', 0 );
    $this->set_variable( 'restrict_qnaire_id', $restrict_qnaire_id );
    $db_restrict_qnaire = $restrict_qnaire_id
                        ? new db\qnaire( $restrict_qnaire_id )
                        : NULL;
    
    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $this->set_variable( 'current_date', $current_date );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->set_variable( 'viewing_date', $viewing_date );

    // set the viewing date if it is not "current"
    if( 'current' != $viewing_date ) db\queue::set_viewing_date( $viewing_date );

    $setting_manager = bus\setting_manager::self();
    foreach( $this->get_record_list() as $record )
    {
      // restrict to the current site if the current user is a supervisor
      if( $is_supervisor ) $record->set_site( $session->get_site() );
      else if( !is_null( $db_restrict_site ) ) $record->set_site( $db_restrict_site );
      
      // restrict to the current qnaire
      $record->set_qnaire( $db_restrict_qnaire );

      $this->add_row( $record->id,
        array( 'rank' => $record->rank,
               'enabled' => $setting_manager->get_setting( 'queue state', $record->name ),
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
    if( NULL == $modifier ) $modifier = new db\modifier();
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
    if( NULL == $modifier ) $modifier = new db\modifier();
    $modifier->where( 'rank', '!=', NULL );
    $modifier->order( 'rank' );

    return parent::determine_record_list( $modifier );
  }
}
?>
