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
    if( $session->get_role()->all_sites )
    {
      $site_id = $this->get_argument( 'site_id', 0 );
      if( $site_id ) $this->db_site = lib::create( 'database\site', $site_id );
    }
    else
    {
      $this->db_site = $session->get_site();
    }

    $qnaire_id = $this->get_argument( 'qnaire_id', 0 );
    if( $qnaire_id ) $this->db_qnaire = lib::create( 'database\qnaire', $qnaire_id );

    $language_id = $this->get_argument( 'language_id', 'any' );
    $this->db_language = 'any' == $language_id
                       ? NULL
                       : lib::create( 'database\language', $language_id );

    $current_date = util::get_datetime_object()->format( 'Y-m-d' );
    $viewing_date = $this->get_argument( 'viewing_date', 'current' );
    if( $current_date == $viewing_date ) $viewing_date = 'current';
    $this->viewing_date = $viewing_date;

    // create an associative array with everything we want to display about the queue
    $this->add_item( 'title', 'constant', 'Title' );
    $this->add_item( 'description', 'constant', 'Description' );
    $this->add_item( 'site', 'constant', 'Site' );
    $this->add_item( 'qnaire', 'constant', 'Questionnaire' );
    $this->add_item( 'language', 'constant', 'Language' );
    $this->add_item( 'viewing_date', 'constant', 'Viewing date' );

    // create the participant sub-list widget
    $this->participant_list = lib::create( 'ui\widget\participant_list', $this->arguments );
    $this->participant_list->set_parent( $this );
    $this->participant_list->set_heading( 'Queue participant list' );
    $this->participant_list->set_allow_restrict_state( false );
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

    // set the view's items
    $this->set_item( 'title', $this->get_record()->title, true );
    $this->set_item( 'description', $this->get_record()->description );
    $this->set_item( 'site', $this->db_site ? $this->db_site->name : 'All sites' );
    $this->set_item( 'qnaire', $this->db_qnaire ? $this->db_qnaire->name : 'All questionnaires' );
    $this->set_item(
      'language', is_null( $this->db_language ) ? 'Any Language' : $this->db_language->name );
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
    $database_class_name = lib::get_class_name( 'database\database' );

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    if( !is_null( $this->db_qnaire ) ) $modifier->where( 'qnaire_id', '=', $this->db_qnaire->id );

    $db_queue = $this->get_record();
    $db_queue->set_site( $this->db_site );

    if( !is_null( $this->db_language ) )
    {
      // if the language isn't set, assume it is the application's default language
      $column = sprintf(
        'IFNULL( participant.language_id, %s )',
        $database_class_name::format_string(
          lib::create( 'business\session' )->get_application()->language_id ) );
      $modifier->where( $column, '=', $this->db_language->id );
    }

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
    $database_class_name = lib::get_class_name( 'database\database' );

    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    if( !is_null( $this->db_qnaire ) ) $modifier->where( 'qnaire_id', '=', $this->db_qnaire->id );

    $db_queue = $this->get_record();
    $db_queue->set_site( $this->db_site );

    if( !is_null( $this->db_language ) )
    {
      // if the language isn't set, assume it is the application's default language
      $column = sprintf(
        'IFNULL( participant.language_id, %s )',
        $database_class_name::format_string(
          lib::create( 'business\session' )->get_application()->language_id ) );
      $modifier->where( $column, '=', $this->db_language->id );
    }

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
   * The language to restrict the queue to (may be NULL)
   * @var database\language
   * @access protected
   */
  protected $db_language = NULL;

  /**
   * The viewing date to restrict the queue to
   * @var string
   * @access protected
   */
  protected $viewing_date;
}
