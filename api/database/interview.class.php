<?php
/**
 * interview.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * interview: record
 *
 * @package sabretooth\database
 */
class interview extends has_note
{
  /**
   * Identical to the parent's select method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_site( $db_site, $modifier = NULL, $count = false )
  {
    // if there is no site restriction then just use the parent method
    if( is_null( $db_site ) ) return parent::select( $modifier, $count );

    // left join the participant, participant_primary_address and address tables
    if( is_null( $modifier ) ) $modifier = new modifier();
    $sql = sprintf( ( $count ? 'SELECT COUNT( %s.%s ) ' : 'SELECT %s.%s ' ).
                    'FROM %s '.
                    'LEFT JOIN participant '.
                    'ON %s.participant_id = participant.id '.
                    'LEFT JOIN participant_primary_address '.
                    'ON participant.id = participant_primary_address.participant_id '.
                    'LEFT JOIN address '.
                    'ON participant_primary_address.address_id = address.id '.
                    'WHERE ( participant.site_id = %d '.
                    '  OR ( participant.site_id IS NULL '.
                    '    AND address.region_id IN ( '.
                    '      SELECT id FROM region WHERE site_id = %d ) ) ) %s',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    static::get_table_name(),
                    static::get_table_name(),
                    $db_site->id,
                    $db_site->id,
                    $modifier->get_sql( true ) );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_site( $db_site, $modifier = NULL )
  {
    return static::select_for_site( $db_site, $modifier, true );
  }
  
  /**
   * Returns the time in seconds that it took to complete a particular phase of this interview
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param db\phase $db_phase Which phase of the interview to get the time of.
   * @param db\assignment $db_assignment Repeated phases have their times measured for each
   *                      iteration of the phase.  For repeated phases this determines which
   *                      assignment's time to return.  It is ignored for phases which are not
   *                      repeated.
   * @return float
   * @access public
   */
  public function get_interview_time( $db_phase, $db_assignment = NULL )
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine interview time for interview with no id.' );
      return 0.0;
    }
    
    if( is_null( $db_phase ) )
    {
      log::warning( 'Tried to determine interview time for null phase.' );
      return 0.0;
    }

    if( $db_phase->repeated && is_null( $db_assignment ) )
    {
      log::warning(
        'Tried to determine interview time for repeating phase without an assignment.' );
      return 0.0;
    }
    
    limesurvey\survey::set_sid( $db_phase->sid );
    $survey_mod = new modifier();
    $survey_mod->where( 'token', '=',
      limesurvey\tokens::determine_token_string( $this, $db_assignment ) );
    $survey_list = limesurvey\survey::select( $survey_mod );

    if( 0 == count( $survey_list ) ) return 0.0;

    if( 1 < count( $survey_list ) ) log::alert( sprintf(
      'There are %d surveys using the same token (%s)! for SID %d',
      count( $survey_list ),
      $token,
      $db_phase->sid ) );

    $db_survey = current( $survey_list );

    limesurvey\survey_timings::set_sid( $db_phase->sid );
    $timing_mod = new modifier();
    $timing_mod->where( 'id', '=', $db_survey->id );
    $db_timings = current( limesurvey\survey_timings::select( $timing_mod ) );
    return $db_timings ? (float) $db_timings->interviewtime : 0.0;
  }

  /**
   * Forces an interview to become completed.
   * 
   * This method will update an interview's status to be complete.  It will also update the
   * correspinding limesurvey data to be set as complete.  This action cannot be undone.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function force_complete()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to force complete interview with no id.' );
      return;
    }
    
    // do nothing if the interview is already set as completed
    if( $this->completed ) return;
    
    // update all uncomplete tokens and surveys associated with this interview which are
    // associated with phases which are not repeated (tokens should not include assignments)
    $phase_mod = new modifier();
    $phase_mod->where( 'repeated', '!=', true );
    foreach( $this->get_qnaire()->get_phase_list( $phase_mod ) as $db_phase )
    {
      // update tokens
      limesurvey\tokens::set_sid( $db_phase->sid );
      $tokens_mod = new modifier();
      $tokens_mod->where( 'token', '=',
        limesurvey\tokens::determine_token_string( $this ) );
      $tokens_mod->where( 'completed', '=', 'N' );
      foreach( limesurvey\tokens::select( $tokens_mod ) as $db_tokens )
      {
        $db_tokens->completed = util::get_datetime_object()->format( 'Y-m-d H:i' );
        $db_tokens->usesleft = 0;
        $db_tokens->save();
      }

      // update surveys
      limesurvey\survey::set_sid( $db_phase->sid );
      $survey_mod = new modifier();
      $survey_mod->where( 'token', '=',
        limesurvey\tokens::determine_token_string( $this ) );
      $survey_mod->where( 'submitdate', '=', NULL );

      // get the last page for this survey
      $lastpage = limesurvey\survey::db()->get_one( sprintf(
        'SELECT MAX( lastpage ) FROM %s',
        limesurvey\survey::get_table_name() ) );

      foreach( limesurvey\survey::select( $survey_mod ) as $db_survey )
      {
        $db_survey->submitdate = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
        if( $lastpage ) $db_survey->lastpage = $lastpage;
        $db_survey->save();
      }
    }

    // finally, update the record
    $this->completed = true;
    $this->save();
  }

  /**
   * Overrides the parent method in order to synchronize the recordings on file with those in
   * the database.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @access public
   */
  public function get_recording_list( $modifier = NULL, $count = false )
  {
    // make sure that all recordings on disk have a corresponding database record
    $monitor_path = WEB_PATH.'/'.bus\setting_manager::self()->get_setting( 'voip', 'monitor' );
    if( is_dir( $monitor_path ) )
    {
      $values = '';
      $first = true;
      foreach( glob( sprintf( '%s/%d_*-out.wav', $monitor_path, $this->id ) ) as $filename )
      {
        $parts = preg_split( '/[-_]/', $filename );
        if( 3 <= count( $parts ) )
        {
          $assignment_id = 0 < $parts[1] ? $parts[1] : 'NULL';
          $rank = 4 <= count( $parts ) ? $parts[2] + 1 : 1;
          $values .= sprintf( '%s( %d, %s, %d, false )',
                              $first ? '' : ', ',
                              $this->id,
                              $assignment_id,
                              $rank );
          $first = false;
        }
      }

      if( !$first )
      {
        static::db()->execute( sprintf(
          'INSERT IGNORE INTO recording ( interview_id, assignment_id, rank, processed ) '.
          'VALUES %s', $values ) );
      }
    }

    return parent::get_recording_list( $modifier, $count );
  }
}
?>
