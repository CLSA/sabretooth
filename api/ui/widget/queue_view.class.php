<?php
/**
 * queue_view.class.php
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
 * widget queue view
 * 
 * @package sabretooth\ui
 */
class queue_view extends base_view
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
    parent::__construct( 'queue', 'view', $args );
    
    $site_id = $this->get_argument( 'site_id' );
    if( $site_id ) $this->db_site = new db\site( $site_id );

    $qnaire_id = $this->get_argument( 'qnaire_id' );
    if( $qnaire_id ) $this->db_qnaire = new db\qnaire( $qnaire_id );

    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->viewing_date = $viewing_date;

    // create an associative array with everything we want to display about the queue
    $this->add_item( 'title', 'constant', 'Title' );
    $this->add_item( 'description', 'constant', 'Description' );
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'qnaire', 'constant', 'Questionnaire' );
    $this->add_item( 'viewing_date', 'constant', 'Viewing date' );

    try
    {
      // create the participant sub-list widget
      $this->participant_list = new participant_list( $args );
      $this->participant_list->set_parent( $this );
      $this->participant_list->set_heading( 'Queue participant list' );
    }
    catch( exc\permission $e )
    {
      $this->participant_list = NULL;
    }
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
    
    // set the view's items
    $this->set_item( 'title', $this->get_record()->title, true );
    $this->set_item( 'description', $this->get_record()->description );
    $this->set_item( 'site', $this->db_site ? $this->db_site->name : 'All sites' );
    $this->set_item( 'qnaire', $this->db_qnaire ? $this->db_qnaire->name : 'All questionnaires' );
    $this->set_item( 'viewing_date', $this->viewing_date );

    $this->finish_setting_items();

    // finish the child widgets
    if( !is_null( $this->participant_list ) )
    {
      $this->participant_list->finish();
      $this->set_variable( 'participant_list', $this->participant_list->get_variables() );
    }
  }

  /**
   * Overrides the participant list widget's method to only include this queue's participant.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_participant_count( $modifier = NULL )
  {
    $db_queue = $this->get_record();
    $db_queue->set_site( $this->db_site );
    $db_queue->set_qnaire( $this->db_qnaire );
    return $db_queue->get_participant_count( $modifier );
  }

  /**
   * Overrides the participant list widget's method to only include this queue's participant.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_participant_list( $modifier = NULL )
  {
    $db_queue = $this->get_record();
    $db_queue->set_site( $this->db_site );
    $db_queue->set_qnaire( $this->db_qnaire );
    return $db_queue->get_participant_list( $modifier );
  }

  /**
   * The participant list widget.
   * @var participant_list
   * @access protected
   */
  protected $participant_list = NULL;

  /**
   * The site to restrict the queue to (may be NULL)
   * @var db\site
   * @access protected
   */
  protected $db_site = NULL;

  /**
   * The qnaire to restrict the queue to (may be NULL)
   * @var db\qnaire
   * @access protected
   */
  protected $db_qnaire = NULL;

  /**
   * The viewing date to restrict the queue to
   * @var string
   * @access protected
   */
  protected $viewing_date;
}
?>
