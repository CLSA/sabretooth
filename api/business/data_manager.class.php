<?php
/**
 * data_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * A manager to provide various data to external sources based on string-based keys
 */
class data_manager extends \cenozo\business\data_manager
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct()
  {
    // nothing required
  }

  /**
   * Get participant-based data
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant
   * @param string $key The key string defining which data to return
   * @return string
   * @access public
   */
  public function get_participant_value( $db_participant, $key )
  {
    // make sure the db_participant object is valid
    if( is_null( $db_participant ) ||
        false === strpos( get_class( $db_participant ), 'database\participant' ) )
      throw lib::create( 'exception\argument', 'db_participant', $db_participant, __METHOD__ );

    // parse the key
    $parts = $this->parse_key( $key, true );
    $subject = $parts[0];

    $value = NULL;
    if( 'limesurvey' == $subject )
    {
      // participant.limesurvey.qnaire.<q>.phase.<p>.question.<code>.<first|last><.notnull> or
      // limesurvey.qnaire.<q>.phase.<p>.question.<code>.<first|last><.notnull>

      // omitting the second last parameter will return the first response
      // omitting the last parameter will return null responses
      if( !( 7 <= count( $parts ) && count( $parts ) <= 9 ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      // validate text parameters
      if( 'qnaire' != $parts[1] ||
          'phase' != $parts[3] ||
          'question' != $parts[5] )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
      $interview_class_name = lib::get_class_name( 'database\interview' );
      $phase_class_name = lib::get_class_name( 'database\phase' );
     
      // get the ranks and make sure they are numbers
      $qnaire_rank = $parts[2];
      if( !util::string_matches_int( $qnaire_rank ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $phase_rank = $parts[4];
      if( !util::string_matches_int( $phase_rank ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      $question = $parts[6];

      $response = 'first';
      if( 7 < count( $parts ) )
      {
        if( 'first' != $parts[7] && 'last' != $parts[7] )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
        $response = $parts[7];
      }

      $notnull = false;
      if( 8 < count( $parts ) )
      {
        if( 'notnull' != $parts[8] )
          throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
        $notnull = true;
      }
      
      // get the participant's interview by rank
      $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $qnaire_rank );
      if( !is_null( $db_qnaire ) )
      {
        $db_interview = $interview_class_name::get_unique_record(
          array( 'participant_id', 'qnaire_id' ),
          array( $db_participant->id, $db_qnaire->id ) );
        
        // get the phase by rank
        $db_phase = $phase_class_name::get_unique_record(
          array( 'qnaire_id', 'rank' ),
          array( $db_qnaire->id, $phase_rank ) );
        if( !is_null( $db_interview ) && !is_null( $db_phase ) )
        {
          $limesurvey_manager = lib::create( 'business\limesurvey_manager' );
          $value = $limesurvey_manager->get_value(
            $db_interview, $db_phase, $question, 'last' == $response, $notnull );
        }
      }
    }
    else if( 'participant' == $subject && 2 == count( $parts ) && 'override_quota()' == $parts[1] )
    {
      // participant.override_quota()
      $value = false === $db_participant->get_quota_enabled() &&
               ( $db_participant->override_quota || $db_participant->get_source()->override_quota )
             ? '1'
             : '0';
    }
    else if( 'qnaire' == $subject )
    {
      $event_class_name = lib::get_class_name( 'database\event' );
      $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

      // participant.qnaire.<rank>.first_attempt_event.<column> or
      // qnaire.<rank>.first_attempt_event.<column> or
      // participant.qnaire.<rank>.reached_event.<column> or
      // qnaire.<rank>.reached_event.<column> or
      // participant.qnaire.<rank>.completed_event.<column> or
      // qnaire.<rank>.completed_event.<column>
      // NOTE: response will be 'DATE UNKNOWN' if null
      if( 4 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $qnaire_rank = $parts[1];
      if( !util::string_matches_int( $qnaire_rank ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $event_type_name = $parts[2];
      if( 'first_attempt_event' != $event_type_name &&
          'reached_event' != $event_type_name &&
          'completed_event' != $event_type_name )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );
      
      $column = $parts[3];

      $event_class_name = lib::get_class_name( 'database\event' );
      if( !$event_class_name::column_exists( $column ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', $qnaire_rank );
      if( !is_null( $db_qnaire ) )
      {
        $db_event_type = NULL;
        $method = sprintf( 'get_%s_type', $event_type_name );
        $db_event_type = $db_qnaire->$method();

        if( !is_null( $db_event_type ) )
        {
          $event_mod = lib::create( 'database\modifier' );
          $event_mod->where( 'event_type_id', '=', $db_event_type->id );
          $event_mod->order_desc( 'datetime' );
          $event_list = $db_participant->get_event_list( NULL, $event_mod );
          if( 0 < count( $event_list ) )
          {
            if( !array_key_exists( $column, $event_list ) )
              throw lib::create( 'exception\argument', 'column', $column, __METHOD__ );
            $value = $event_list[0][$column];
          }
        }
      }
    }
    else
    {
      // to be handled by the base class
      $value = parent::get_participant_value( $db_participant, $key );
    }

    return $value;
  }
}
