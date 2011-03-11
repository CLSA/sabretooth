<?php
/**
 * survey_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget survey list
 * 
 * @package sabretooth\ui
 */
class survey_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the survey list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'survey', $args );
    
    $session = \sabretooth\session::self();

    $this->add_column( 'sid', 'Limesurvey ID', false );
    $this->add_column( 'title', 'Title', false );
    $this->add_column( 'language', 'Main Language', false );
    $this->add_column( 'additional_languages', 'Other Languages', false );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // get all surveys
    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->sid,
        array( 'sid' => $record->sid,
               'title' => $record->get_title(),
               'language' => $record->language,
               'additional_languages' => $record->additional_languages ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overriding parent method because we need to query limesurvey database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'active', '=', 'Y' );
    return \sabretooth\database\limesurvey\surveys::count( $modifier );
  }

  /**
   * Overriding parent method because we need to query limesurvey database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( NULL == $modifier ) $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'active', '=', 'Y' );
    return \sabretooth\database\limesurvey\surveys::select( $modifier );
  }
}
?>
