<?php
/**
 * participant_tree.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant tree
 * 
 * @package sabretooth\ui
 */
class participant_tree extends \cenozo\ui\widget
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
    $session = lib::create( 'business\session' );
    if( 3 > $session->get_role()->tier )
      $this->set_heading( $this->get_heading().' for '.$session->get_site()->name );
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
    
    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;
    $is_mid_tier = 2 == $session->get_role()->tier;
    
    // if this is a top tier role give them a list of sites to choose from
    if( $is_top_tier )
    {
      $sites = array();
      $site_class_name = lib::get_class_name( 'database\site' );
      foreach( $site_class_name::select() as $db_site )
        $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }

    $restrict_site_id = $this->get_argument( "restrict_site_id", 0 );
    $this->set_variable( 'restrict_site_id', $restrict_site_id );
    $db_restrict_site = $restrict_site_id
                      ? lib::create( 'database\site', $restrict_site_id )
                      : NULL;
    
    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $this->set_variable( 'current_date', $current_date );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->set_variable( 'viewing_date', $viewing_date );

    // set the viewing date if it is not "current"
    $queue_class_name = lib::get_class_name( 'database\queue' );
    if( 'current' != $viewing_date ) $queue_class_name::set_viewing_date( $viewing_date );

    $show_queue_index = $this->get_argument( 'show_queue_index', NULL );
    if( is_null( $show_queue_index ) )
    {
      $db_show_queue = $queue_class_name::get_unique_record( 'name', 'qnaire' );
      $show_qnaire_id = 0;
    }
    else
    {
      $parts = explode( '_', $show_queue_index );
      $show_qnaire_id = $parts[0];
      $db_show_queue = lib::create( 'database\queue', $parts[1] );
    }
    
    // build the tree from the root
    $nodes = array();
    $tree = array(); // NOTE: holds references to the nodes array
    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'parent_queue_id' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    foreach( $queue_class_name::select( $modifier ) as $db_queue )
    {
      // restrict to the current site if the current user is a mid tier role
      if( $is_mid_tier ) $db_queue->set_site( $session->get_site() );
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
        $modifier = lib::create( 'database\modifier' );
        $modifier->order( 'rank' );

        foreach( $qnaire_class_name::select( $modifier ) as $db_qnaire )
        {
          $db_queue->set_qnaire( $db_qnaire );
          
          $index = sprintf( '%d_%d', $db_qnaire->id, $db_queue->id );
          $title = 'qnaire' == $db_queue->name
                 ? sprintf( 'Questionnaire #%d: "%s"', $db_qnaire->rank, $db_qnaire->name )
                 : $db_queue->title;
          $open = $db_show_queue->id == $db_queue->id && $show_qnaire_id == $db_qnaire->id;

          /* TODO: the following is disabled for now, need to improve queue querying
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
          */
          $count = $db_queue->get_participant_count();

          $nodes[$index] = array( 'id' => $index,
                                  'title' => $title,
                                  'open' => $open,
                                  'rank' => $db_queue->rank,
                                  'count' => $count,
                                  'children' => array() );

          // add as a branch to parent node
          $db_parent_queue = lib::create( 'database\queue', $db_queue->parent_queue_id );
          $parent_index = sprintf( '%d_%d',
            $db_parent_queue->qnaire_specific ? $db_qnaire->id : 0,
            $db_queue->parent_queue_id );
          $nodes[$parent_index]['children'][] = &$nodes[$index];
        }
      }
    }

    // make sure that all ancestor's of the show queue are open
    $db_queue = lib::create( 'database\queue', $db_show_queue->parent_queue_id );
    
    do
    {
      $index = sprintf( '%d_%d',
        $db_queue->qnaire_specific ? $show_qnaire_id : 0,
        $db_queue->id );
      $nodes[$index]['open'] = true;
      $db_queue = lib::create( 'database\queue', $db_queue->parent_queue_id );
    } while( !is_null( $db_queue->parent_queue_id ) );
    
    $this->set_variable( 'tree', $tree );
  }
}
?>
