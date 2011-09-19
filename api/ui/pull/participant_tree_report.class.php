<?php
/**
 * participant_tree.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Consent form report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class participant_tree_report extends base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant_tree', $args );
  }

  public function finish()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = new db\qnaire( $this->get_argument( 'qnaire_id' ) );
    
    $site_mod = new db\modifier();
    $title = 'Participant Tree Report';
    if( $restrict_site_id )
    {
      $db_restrict_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_restrict_site->name;
      $site_mod->where( 'id', '=', $db_restrict_site );
    }
    $this->add_title( $title );
    $this->add_title( 'Generated for '.$db_qnaire->name );

    $contents = array();

    // The following code is very similar to the participant_tree widget
    // We loop through every queue to get the number of participants waiting in it
    foreach( db\queue::select() as $db_queue )
    {
      $row = array( $db_queue->title );

      foreach( db\site::select( $site_mod ) as $db_site )
      {
        // restrict by site, if necessary
        $db_queue->set_site( $db_site );
        $db_queue->set_qnaire( $db_qnaire );
        $row[] = $db_queue->get_participant_count();
      }

      // add the grand total if we are not restricting by site
      if( !$restrict_site_id )
      {
        $db_queue->set_site( NULL );
        $row[] = $db_queue->get_participant_count();
      }

      $contents[] = array( $db_queue->title, $db_queue->get_participant_count() );
    }
    
    if( $restrict_site_id )
    {
      $header = array( 'Queue', 'Total' );
    }
    else
    {
      $header = array( 'Queue' );
      foreach( db\site::select( $site_mod ) as $db_site ) $header[] = $site->name;
      $header[] = 'Total';
    }

    $this->add_table( NULL, $header, $contents, NULL );

    return parent::finish();
  }
}
?>
