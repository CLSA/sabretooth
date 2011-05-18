<?php
/**
 * qnaire_add.class.php
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
 * widget qnaire add
 * 
 * @package sabretooth\ui
 */
class qnaire_add extends base_view
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
    parent::__construct( 'qnaire', 'add', $args );
    
    // define all columns defining this record
    $this->add_item( 'name', 'string', 'Name' );
    $this->add_item( 'rank', 'enum', 'Rank' );
    $this->add_item( 'prev_qnaire_id', 'enum', 'Previous Questionnaire',
      'The questionnaire which must be finished before this one begins.' );
    $this->add_item( 'delay', 'number', 'Delay (weeks)',
      'How many weeks after the previous questionnaire is completed before this one may begin.' );
    $this->add_item( 'description', 'text', 'Description' );
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
    
    // create enum arrays
    $qnaires = array();
    foreach( db\qnaire::select() as $db_qnaire ) $qnaires[$db_qnaire->id] = $db_qnaire->name;
    $num_ranks = db\qnaire::count();
    $ranks = array();
    for( $rank = 1; $rank <= ( $num_ranks + 1 ); $rank++ ) $ranks[] = $rank;
    $ranks = array_combine( $ranks, $ranks );
    end( $ranks );
    $last_rank_key = key( $ranks );
    reset( $ranks );

    // set the view's items
    $this->set_item( 'name', '', true );
    $this->set_item( 'rank', $last_rank_key, true, $ranks );
    $this->set_item( 'prev_qnaire_id', key( $qnaires ), false, $qnaires );
    $this->set_item( 'delay', 52, true );
    $this->set_item( 'description', '' );

    $this->finish_setting_items();
  }
}
?>
