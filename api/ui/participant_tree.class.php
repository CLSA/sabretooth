<?php
/**
 * participant_tree.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
    $session = \sabretooth\business\session::self();
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

    // build the tree from the root
    $nodes = array();
    $tree = array(); // NOTE: holds references to the nodes array
    $modifier = new \sabretooth\database\modifier();
    $modifier->order( 'parent_queue_id' );
    foreach( \sabretooth\database\queue::select( $modifier ) as $db_queue )
    {
      // restrict to the current site if the current user is a supervisor
      if( $is_supervisor ) $db_queue->set_site( $session->get_site() );
      else if( !is_null( $db_restrict_site ) ) $db_queue->set_site( $db_restrict_site );

      // the first two nodes should not be repeated for every qnaire
      if( 1 == $db_queue->id || 2 == $db_queue->id )
      {
        $index = implode( '_', array(0, $db_queue->id ) );
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
          $parent_index = implode( '_', array( 0, $db_queue->parent_queue_id ) );
          $nodes[$parent_index]['children'][] = &$nodes[$index];
        }
      }
      else
      {
        foreach( \sabretooth\database\qnaire::select() as $db_qnaire )
        {
          $db_queue->set_qnaire( $db_qnaire );
          
          $index = implode( '_', array( $db_qnaire->id, $db_queue->id ) );
          $title = 'qnaire' == $db_queue->name
                 ? 'Questionnaire: "'.$db_qnaire->name.'"'
                 : $db_queue->title;
          $nodes[$index] = array( 'id' => $index,
                                  'title' => $title,
                                  'open' => 'qnaire' == $db_queue->name,
                                  'rank' => $db_queue->rank,
                                  'count' => $db_queue->get_participant_count(),
                                  'children' => array() );
          if( is_null( $db_queue->parent_queue_id ) )
          { // insert as a root node (careful, nodes are being passed by reference!)
            $tree[] = &$nodes[$index];
          }
          else
          { // add as a branch to parent node
            $parent_index = 1 == $db_queue->parent_queue_id || 2 == $db_queue->parent_queue_id
                            ? implode( '_', array( 0, $db_queue->parent_queue_id ) )
                            : implode( '_', array( $db_qnaire->id, $db_queue->parent_queue_id ) );
            $nodes[$parent_index]['children'][] = &$nodes[$index];

            if( !is_null( $nodes[$index]['rank'] ) )
            { // open the parent branch if this branch is a queue
              $nodes[$parent_index]['open'] = true;
            }
          }
        }
      }
    }
    
    $this->set_variable( 'tree', $tree );
  }
}
?>
