<?php
/**
 * participant_tree.class.php
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
 * widget participant tree
 * 
 * @package sabretooth\ui
 */
class participant_tree extends widget
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
    parent::__construct( 'participant', 'tree', $args );
    $session = bus\session::self();
    $this->set_heading( 'supervisor' == $session->get_role()->name ?
      'Participant tree for '.$session->get_site()->name : 'Participant tree' );
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

    $restrict_site_id = $this->get_argument( "restrict_site_id", 0 );
    $this->set_variable( 'restrict_site_id', $restrict_site_id );
    $db_restrict_site = $restrict_site_id
                      ? new db\site( $restrict_site_id )
                      : NULL;
    
    $show_queue_index = $this->get_argument( 'show_queue_index', NULL );
    if( is_null( $show_queue_index ) )
    {
      $db_show_queue = db\queue::get_unique_record( 'name', 'qnaire' );
      $show_qnaire_id = 0;
    }
    else
    {
      $parts = explode( '_', $show_queue_index );
      $show_qnaire_id = $parts[0];
      $db_show_queue = new db\queue( $parts[1] );
    }

    // build the tree from the root
    $nodes = array();
    $tree = array(); // NOTE: holds references to the nodes array
    $modifier = new db\modifier();
    $modifier->order( 'parent_queue_id' );
    foreach( db\queue::select( $modifier ) as $db_queue )
    {
      // restrict to the current site if the current user is a supervisor
      if( $is_supervisor ) $db_queue->set_site( $session->get_site() );
      else if( !is_null( $db_restrict_site ) ) $db_queue->set_site( $db_restrict_site );

      // handle queues which are not qnaire specific
      if( !$db_queue->qnaire_specific )
      {
        $index = sprintf( '%d_%d', 0, $db_queue->id );
        $nodes[$index] = array( 'id' => $index,
                                'title' => $db_queue->title,
                                'open' => 1 == $db_queue->id,
                                'count' => $db_queue->get_participant_count(),
                                'children' => array() );
        if( is_null( $db_queue->parent_queue_id ) )
        { // insert as a root node (careful, nodes are being passed by reference!)
          $tree[] = &$nodes[$index];
        }
        else
        { // add as a branch to parent node
          $parent_index = sprintf( '%d_%d', 0, $db_queue->parent_queue_id );
          $nodes[$parent_index]['children'][] = &$nodes[$index];
        }
      }
      else // handle queues which are qnaire specific
      {
        $modifier = new db\modifier();
        $modifier->order( 'rank' );
        foreach( db\qnaire::select( $modifier ) as $db_qnaire )
        {
          $db_queue->set_qnaire( $db_qnaire );
          
          $index = sprintf( '%d_%d', $db_qnaire->id, $db_queue->id );
          $title = 'qnaire' == $db_queue->name
                 ? sprintf( 'Questionnaire #%d: "%s"', $db_qnaire->rank, $db_qnaire->name )
                 : $db_queue->title;
          $open = $db_show_queue->id == $db_queue->id && $show_qnaire_id == $db_qnaire->id;
          // don't count the participants in hidden branches
          $count = -1;
          if( // always show all qnaire queues or...
              'qnaire' == $db_queue->name || (
                // if in the selected qnaire tree and...
                $show_qnaire_id == $db_qnaire->id && (
                  // this is the selected node or...
                  $db_show_queue->id >= $db_queue->id ||
                  // the parent queue is the selected node or...
                  $db_show_queue->id == $db_queue->parent_queue_id ||
                  // a sibling node is the selected node
                  $db_show_queue->parent_queue_id == $db_queue->parent_queue_id ) ) )
          {
            $count = $db_queue->get_participant_count();
          }

          $nodes[$index] = array( 'id' => $index,
                                  'title' => $title,
                                  'open' => $open,
                                  'rank' => $db_queue->rank,
                                  'count' => $count,
                                  'children' => array() );

          // add as a branch to parent node
          $db_parent_queue = new db\queue( $db_queue->parent_queue_id );
          $parent_index = sprintf( '%d_%d',
            $db_parent_queue->qnaire_specific ? $db_qnaire->id : 0,
            $db_queue->parent_queue_id );
          $nodes[$parent_index]['children'][] = &$nodes[$index];
        }
      }
    }

    // make sure that all ancestor's of the show queue are open
    $db_queue = new db\queue( $db_show_queue->parent_queue_id );
    
    do
    {
      $index = sprintf( '%d_%d',
        $db_queue->qnaire_specific ? $show_qnaire_id : 0,
        $db_queue->id );
      $nodes[$index]['open'] = true;
      $db_queue = new db\queue( $db_queue->parent_queue_id );
    } while( !is_null( $db_queue->parent_queue_id ) );
    
    $this->set_variable( 'tree', $tree );
  }
}
?>
