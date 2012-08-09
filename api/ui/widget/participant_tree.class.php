<?php
/**
 * participant_tree.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant tree
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
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    $session = lib::create( 'business\session' );
    $is_top_tier = 3 == $session->get_role()->tier;
    
    // if this is a top tier role give them a list of sites to choose from
    if( $is_top_tier )
    {
      $sites = array();
      $class_name = lib::get_class_name( 'database\site' );
      foreach( $class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }
    else // otherwise show in the header which site the tree is for
    {
      $this->set_heading( $this->get_heading().' for '.$session->get_site()->name );
    }

    $site_id = $this->get_argument( "site_id", 0 );
    $this->set_variable( 'site_id', $site_id );
    $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
    
    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $this->set_variable( 'current_date', $current_date );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->set_variable( 'viewing_date', $viewing_date );

    // set the viewing date if it is not "current"
    $class_name = lib::get_class_name( 'database\queue' );
    if( 'current' != $viewing_date ) $class_name::set_viewing_date( $viewing_date );

    $show_queue_index = $this->get_argument( 'show_queue_index', NULL );
    if( is_null( $show_queue_index ) )
    {
      $db_show_queue = $class_name::get_unique_record( 'name', 'qnaire' );
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
    foreach( $class_name::select( $modifier ) as $db_queue )
    {
      // restrict queue based on user's role
      if( !$is_top_tier ) $db_queue->set_site( $session->get_site() );
      else if( !is_null( $db_site ) ) $db_queue->set_site( $db_site );
      
      // handle queues which are not qnaire specific
      if( !$db_queue->qnaire_specific )
      {
        $index = sprintf( '%d_%d', 0, $db_queue->id );
        $nodes[$index] = array( 'id' => $index,
                                'title' => $db_queue->title,
                                'open' => 1 == $db_queue->id,
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
        $class_name = lib::get_class_name( 'database\qnaire' );
        foreach( $class_name::select( $modifier ) as $db_qnaire )
        {
          $db_queue->set_qnaire( $db_qnaire );
          
          $index = sprintf( '%d_%d', $db_qnaire->id, $db_queue->id );
          $title = 'qnaire' == $db_queue->name
                 ? sprintf( 'Questionnaire #%d: "%s"', $db_qnaire->rank, $db_qnaire->name )
                 : $db_queue->title;
          $open = $db_show_queue->id == $db_queue->id && $show_qnaire_id == $db_qnaire->id;

          $nodes[$index] = array( 'id' => $index,
                                  'title' => $title,
                                  'open' => $open,
                                  'rank' => $db_queue->rank,
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
