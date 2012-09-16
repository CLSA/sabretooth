<?php
/**
 * queue_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget queue view
 */
class queue_view extends \cenozo\ui\widget\base_view
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
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    $session = lib::create( 'business\session' );
    if( 3 != $session->get_role()->tier )
    {
      $this->db_site = $session->get_site();
    }
    else
    {
      $site_id = $this->get_argument( 'site_id' );
      if( $site_id ) $this->db_site = lib::create( 'database\site', $site_id );
    }

    $qnaire_id = $this->get_argument( 'qnaire_id' );
    if( $qnaire_id ) $this->db_qnaire = lib::create( 'database\qnaire', $qnaire_id );

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

    // create the participant sub-list widget
    $this->participant_list = lib::create( 'ui\widget\participant_list', $this->arguments );
    $this->participant_list->set_parent( $this );
    $this->participant_list->set_heading( 'Queue participant list' );
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
    
    // set the view's items
    $this->set_item( 'title', $this->get_record()->title, true );
    $this->set_item( 'description', $this->get_record()->description );
    $this->set_item( 'site', $this->db_site ? $this->db_site->name : 'All sites' );
    $this->set_item( 'qnaire', $this->db_qnaire ? $this->db_qnaire->name : 'All questionnaires' );
    $this->set_item( 'viewing_date', $this->viewing_date );

    // process the child widgets
    try
    {
      $this->participant_list->process();
      // can't sort by the source
      $this->participant_list->add_column( 'source.name', 'string', 'Source', false );
      $this->participant_list->execute();
      $this->set_variable( 'participant_list', $this->participant_list->get_variables() );
    }
    catch( \cenozo\exception\permission $e ) {}
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
    // replace participant. with participant_ in the where columns of the modifier
    // (see queue record's participant_for_queue for details)
    if( !is_null( $modifier ) )
      foreach( $modifier->get_where_columns() as $column )
        $modifier->change_where_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );

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
    // replace participant. with participant_ in the where and order columns of the modifier
    // (see queue record's participant_for_queue for details)
    if( !is_null( $modifier ) )
    {
      foreach( $modifier->get_where_columns() as $column )
        $modifier->change_where_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );
      foreach( $modifier->get_order_columns() as $column )
        $modifier->change_order_column(
          $column, preg_replace( '/^participant\./', 'participant_', $column ) );
    }

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
   * @var database\site
   * @access protected
   */
  protected $db_site = NULL;

  /**
   * The qnaire to restrict the queue to (may be NULL)
   * @var database\qnaire
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
