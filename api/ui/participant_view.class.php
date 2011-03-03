<?php
/**
 * participant_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget participant view
 * 
 * @package sabretooth\ui
 */
class participant_view extends base_view
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
    parent::__construct( 'participant', 'view', $args );
    
    // create enum arrays
    $languages = \sabretooth\database\participant::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $status_values = \sabretooth\database\participant::get_enum_values( 'status' );
    $statuses = array( 'NULL' => '' ); // add a blank entry
    $statuses = array_merge( $statuses, array_combine( $status_values, $status_values ) );
    $sites = array( 'NULL' => '' ); // add a blank entry
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;

    // create an associative array with everything we want to display about the participant
    $this->item['first_name'] =
      array( 'heading' => 'First Name',
             'type' => 'string',
             'value' => $this->get_record()->first_name );
    $this->item['last_name'] =
      array( 'heading' => 'Last Name',
             'type' => 'string',
             'value' => $this->get_record()->last_name );
    $this->item['language'] =
      array( 'heading' => 'Language',
             'type' => 'enum',
             'enum' => $languages,
             'value' => $this->get_record()->language );
    $this->item['hin'] =
      array( 'heading' => 'Health Insurance Number',
             'type' => 'string',
             'value' => $this->get_record()->hin );
    $this->item['status'] =
      array( 'heading' => 'Condition',
             'type' => 'enum',
             'enum' => $statuses,
             'value' => $this->get_record()->status );
    $this->item['site_id'] =
      array( 'heading' => 'Site',
             'type' => 'enum',
             'enum' => $sites,
             'value' => $this->get_record()->get_site()->name );

    // create the sample sub-list widget
    $this->sample_list = new sample_list( $args );
    $this->sample_list->set_parent( $this );
    $this->sample_list->set_heading( 'Samples the participant belongs to' );
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

    $this->sample_list->finish();
    $this->set_variable( 'sample_list', $this->sample_list->get_variables() );
  }
  
  /**
   * The participant list widget.
   * @var sample_list
   * @access protected
   */
  protected $sample_list = NULL;
}
?>
