<?php
/**
 * shift_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget shift view
 * 
 * @package sabretooth\ui
 */
class shift_view extends base_view
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
    parent::__construct( 'shift', 'view', $args );
    
    // create an associative array with everything we want to display about the shift
    $this->add_item( 'first_name', 'string', 'First Name' );
    $this->add_item( 'last_name', 'string', 'Last Name' );
    $this->add_item( 'language', 'enum', 'Language' );
    $this->add_item( 'hin', 'string', 'Health Insurance Number' );
    $this->add_item( 'status', 'enum', 'Condition' );
    $this->add_item( 'site_id', 'enum', 'Site' );

    // create the sample sub-list widget
    $this->sample_list = new sample_list( $args );
    $this->sample_list->set_parent( $this );
    $this->sample_list->set_heading( 'Samples the shift belongs to' );
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
    $languages = \sabretooth\database\shift::get_enum_values( 'language' );
    $languages = array_combine( $languages, $languages );
    $status_values = \sabretooth\database\shift::get_enum_values( 'status' );
    $statuses = array( 'NULL' => '' ); // add a blank entry
    $statuses = array_merge( $statuses, array_combine( $status_values, $status_values ) );
    $sites = array( 'NULL' => '' ); // add a blank entry
    foreach( \sabretooth\database\site::select() as $db_site ) $sites[$db_site->id] = $db_site->name;

    // set the view's items
    $this->set_item( 'first_name', $this->get_record()->first_name );
    $this->set_item( 'last_name', $this->get_record()->last_name );
    $this->set_item( 'language', $this->get_record()->language, $languages );
    $this->set_item( 'hin', $this->get_record()->hin );
    $this->set_item( 'status', $this->get_record()->status, $statuses );
    $this->set_item( 'site_id', $this->get_record()->get_site()->name, $sites );

    $this->finish_setting_items();

    // finish the child widgets
    $this->sample_list->finish();
    $this->set_variable( 'sample_list', $this->sample_list->get_variables() );
  }
  
  /**
   * The shift list widget.
   * @var sample_list
   * @access protected
   */
  protected $sample_list = NULL;
}
?>
