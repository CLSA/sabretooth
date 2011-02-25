<?php
/**
 * script_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget script list
 * 
 * @package sabretooth\ui
 */
class script_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the script list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'script', $args );
    
    $session = \sabretooth\session::self();

    $this->columns = array(
      array( 'id' => 'sid',
             'heading' => 'Limesurvey ID',
             'sortable' => true ),
      array( 'id' => 'title',
             'heading' => 'Title',
             'sortable' => true ),
      array( 'id' => 'language',
             'heading' => 'Main Language',
             'sortable' => true ),
      array( 'id' => 'additional_languages',
             'heading' => 'Other Languages',
             'sortable' => true ) );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function set_rows()
  {
    // reset the array
    $this->rows = array();
    
    // get all scripts
    foreach( $this->get_record_list() as $record )
    {
      array_push(
        $this->rows,
        array( 'id' => $record->sid,
               'columns' =>
                 array( 'sid' => $record->sid,
                        'title' => $record->get_title(),
                        'language' => $record->language,
                        'additional_languages' => $record->additional_languages ) ) );
    }
  }

  /**
   * Overriding parent method because we need to query limesurvey database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( active_record )
   * @access protected
   */
  protected function determine_record_list( $modifier )
  {
    $modifier->where( 'active', 'Y' );
    return \sabretooth\database\limesurvey\surveys::select( $modifier );
  }
}
?>
