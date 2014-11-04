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
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    $site_class_name = lib::get_class_name( 'database\site' );
    $language_class_name = lib::get_class_name( 'database\language' );
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $interview_method_class_name = lib::get_class_name( 'database\interview_method' );
    $operation_class_name = lib::get_class_name( 'database\operation' );

    $session = lib::create( 'business\session' );
    $all_sites = $session->get_role()->all_sites;
    
    // if this is an all-site role give them a list of sites to choose from
    if( $all_sites )
    {
      $sites = array();
      foreach( $site_class_name::select() as $db_site ) $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }
    else // otherwise show in the header which site the tree is for
    {
      $this->set_heading( $this->get_heading().' for '.$session->get_site()->name );
    }

    $site_id = $this->get_argument( "site_id", 0 );
    $this->set_variable( 'site_id', $site_id );
    $db_site = $site_id ? lib::create( 'database\site', $site_id ) : NULL;
    
    $language_mod = lib::create( 'database\modifier' );
    $language_mod->where( 'active', '=', true );
    $languages = array( 'any' => 'any' );
    foreach( $language_class_name::select( $language_mod ) as $db_language )
      $languages[$db_language->id] = $db_language->name;
    $this->set_variable( 'languages', $languages );

    $restrict_language_id = $this->get_argument( 'restrict_language_id', 'any' );
    $this->set_variable( 'restrict_language_id', $restrict_language_id );

    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $this->set_variable( 'current_date', $current_date );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->set_variable( 'viewing_date', $viewing_date );

    // set the viewing date if it is not "current"
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

    $db_interview_method = $interview_method_class_name::get_unique_record( 'name', 'ivr' );

    // build the tree from the root
    $nodes = array();
    $tree = array(); // NOTE: holds references to the nodes array
    $modifier = lib::create( 'database\modifier' );
    $modifier->order( 'parent_queue_id' );
    $modifier->order( 'id' );
    if( !$qnaire_class_name::is_interview_method_in_use( $db_interview_method ) )
      $modifier->where( 'name', '!=', 'ivr_appointment' ); // remove IVR if not in use
    foreach( $queue_class_name::select( $modifier ) as $db_queue )
    {
      // restrict queue based on user's role
      if( !$all_sites ) $db_queue->set_site( $session->get_site() );
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
        foreach( $qnaire_class_name::select( $modifier ) as $db_qnaire )
        {
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

    $db_operation = $operation_class_name::get_operation( 'push', 'queue', 'repopulate' );
    $this->set_variable( 'allow_repopulate', $session->is_allowed( $db_operation ) );
  }
}
