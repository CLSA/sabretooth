<?php
/**
 * interview_list.class.php
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
 * widget interview list
 * 
 * @package sabretooth\ui
 */
class interview_list extends site_restricted_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the interview list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'interview', $args );
    
    $this->add_column( 'participant.uid', 'string', 'UID', true );
    $this->add_column( 'qnaire.name', 'string', 'Questionnaire', true );
    $this->add_column( 'completed', 'boolean', 'Completed', true );
    $this->add_column( 'recordings', 'number', 'Recordings', false );
    $this->add_column( 'unprocessed', 'number', 'Unprocessed', false );
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
    
    foreach( $this->get_record_list() as $record )
    {
      $recordings = $record->get_recording_count();
      $recording_mod = new db\modifier();
      $recording_mod->where( 'processed', '=', true );
      $processed_recordings = $record->get_recording_count( $recording_mod );

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'participant.uid' => $record->get_participant()->uid,
               'qnaire.name' => $record->get_qnaire()->name,
               'completed' => $record->completed,
               'recordings' => $recordings,
               'unprocessed' => $recordings - $processed_recordings ) );
    }

    $this->finish_setting_rows();
  }

  /**
   * Overrides the parent class method to restrict interview list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_count( $modifier )
         : db\interview::count_for_site( $this->db_restrict_site, $modifier );
  }
  
  /**
   * Overrides the parent class method to restrict interview list based on user's role
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    return is_null( $this->db_restrict_site )
         ? parent::determine_record_list( $modifier )
         : db\interview::select_for_site( $this->db_restrict_site, $modifier );
  }
}
?>
