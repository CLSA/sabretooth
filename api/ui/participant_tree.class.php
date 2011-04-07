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
    $this->set_heading( 'Participant Tree' );
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
    
    // build the tree from the root
    $nodes = array();
    $tree = array(); // NOTE: holds references to the nodes array
    $modifier = new \sabretooth\database\modifier();
    $modifier->order( 'parent_queue_id' );
    foreach( \sabretooth\database\queue::select( $modifier ) as $db_queue )
    {
      // the first two nodes should not be repeated for every qnaire
      if( 1 == $db_queue->id || 2 == $db_queue->id )
      {
        $index = implode( '_', array(0, $db_queue->id ) );
        $nodes[$index] = array( 'id' => $db_queue->id,
                                'title' => $db_queue->title,
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
          $title = 'qnaire' == $db_queue->name ? $db_qnaire->name : $db_queue->title;
          $nodes[$index] = array( 'id' => $db_queue->id,
                                  'title' => $title,
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
          }
        }
      }
    }
    
    $this->set_variable( 'tree', $tree );
  }
}
?>
