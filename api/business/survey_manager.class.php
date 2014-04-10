<?php
/**
 * survey_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The survey manager is responsible for business-layer survey functionality.
 */
class survey_manager extends \cenozo\singleton
{
  /**
   * Constructor.
   * 
   * Since this class uses the singleton pattern the constructor is never called directly.  Instead
   * use the {@link singleton} method.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function __construct() {}

  /**
   * Gets the current survey URL.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string (or false if the survey is not active)
   * @access public
   */
  public function get_survey_url()
  {
    $session = lib::create( 'business\session' );

    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      // get the participant being sourced
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
      if( is_null( $db_participant ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;
      
      // determine which language to use
      $lang = $db_participant->language;
      if( !$lang ) $lang = 'en';
      
      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) )
    {
      // get the participant being withdrawn
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
      if( is_null( $db_participant ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;

      // determine which language to use
      $lang = $db_participant->language;
      if( !$lang ) $lang = 'en';
      
      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }
    else if( 'operator' == $session->get_role()->name )
    {
      // must have an assignment
      $db_assignment = $session->get_current_assignment();
      if( is_null( $db_assignment ) ) return false;
      
      // the assignment must have an open call
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'end_datetime', '=', NULL );
      $call_list = $db_assignment->get_phone_call_list( $modifier );
      if( 0 == count( $call_list ) ) return false;

      // determine the current sid and token
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false === $sid || false == $token ) return false;
      
      // determine which language to use
      $lang = $db_assignment->get_interview()->get_participant()->language;
      if( !$lang ) $lang = 'en';
      
      return LIMESURVEY_URL.sprintf( '/index.php?sid=%s&lang=%s&token=%s&newtest=Y', $sid, $lang, $token );
    }

    return false;
  }

  /**
   * This method returns the current SID, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_current_sid()
  {
    if( is_null( $this->current_sid ) ) $this->determine_current_sid_and_token();
    return $this->current_sid;
  }

  /**
   * This method returns the current token, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_current_token()
  {
    if( is_null( $this->current_token ) ) $this->determine_current_sid_and_token();
    return $this->current_token;
  }

  /**
   * Determines the current SID and token.
   * 
   * This method will first determine whether the participant needs to complete the withdraw
   * script or a questionnaire.  It then determines whether the appropriate script has been
   * completed or not.
   * Note: This method will create tokens in the limesurvey database as necessary.
   * This is also where interviews are marked as complete once all phases are finished.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function determine_current_sid_and_token()
  {
    $this->current_sid = false;
    $this->current_token = false;

    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $source_withdraw_class_name = lib::get_class_name( 'database\source_withdraw' );
    $source_survey_class_name = lib::get_class_name( 'database\source_survey' );
    $event_type_class_name = lib::get_class_name( 'database\event_type' );

    $session = lib::create( 'business\session' );
    $setting_manager = lib::create( 'business\setting_manager' );

    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      // get the participant being sourced
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
      if( is_null( $db_participant ) )
      {
        log::warning( 'Tried to determine survey information for an invalid participant.' );
        return false;
      }

      $sid = $setting_manager->get_setting( 'general', 'secondary_survey' );
      $token = $_COOKIE['secondary_id'];

      $tokens_class_name::set_sid( $sid );
      $survey_class_name::set_sid( $sid );

      // reset the script and token
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=', $token );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens ) $db_tokens->delete();
      $scripts_mod = lib::create( 'database\modifier' );
      $scripts_mod->where( 'token', '=', $token );
      foreach( $survey_class_name::select( $scripts_mod ) as $db_survey ) $db_survey->delete();

      $db_tokens = lib::create( 'database\limesurvey\tokens' );
      $db_tokens->token = $token;
      $db_tokens->firstname = $db_participant->first_name;
      $db_tokens->lastname = $db_participant->last_name;
      $db_tokens->update_attributes( $db_participant );
      $db_tokens->save();

      // the secondary survey can be brought back up after it is complete, so always set these
      $this->current_sid = $sid;
      $this->current_token = $token;
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) &&
             'operator' != $session->get_role()->name )
    {
      // get the participant being withdrawn
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
      if( is_null( $db_participant ) )
      {
        log::warning( 'Tried to determine survey information for an invalid participant.' );
        return false;
      }

      $db_qnaire = $db_participant->get_effective_qnaire();
      if( is_null( $db_qnaire ) )
      { // finished all qnaires, find the last one completed
        $db_assignment = $db_participant->get_last_finished_assignment();

        if( !is_null( $db_assignment ) )
        {
          $db_qnaire = $db_assignment->get_interview()->get_qnaire();
        }
        else
        { // no interview means we'll just use the first qnaire
          $db_qnaire = $qnaire_class_name::get_unique_record( 'rank', 1 );

          if( is_null( $db_qnaire ) )
            throw lib::create( 'exception\runtime',
                               'Trying to withdraw participant without a questionnaire.',
                               __METHOD__ );
        }
      }

      // let the tokens record class know which SID we are dealing with by checking if
      // there is a source-specific survey for the participant, and if not falling back
      // on the default withdraw survey
      $db_source_withdraw = $source_withdraw_class_name::get_unique_record(
        array( 'qnaire_id', 'source_id' ),
        array( $db_qnaire->id, $db_participant->source_id ) );
      $sid = is_null( $db_source_withdraw ) ? $db_qnaire->withdraw_sid : $db_source_withdraw->sid;

      $survey_class_name::set_sid( $sid );
      $tokens_class_name::set_sid( $sid );
      $token = $db_participant->uid;
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=', $token );
      $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );

      if( false === $db_tokens )
      { // token not found, create it
        $db_tokens = lib::create( 'database\limesurvey\tokens' );
        $db_tokens->token = $token;
        $db_tokens->firstname = $db_participant->first_name;
        $db_tokens->lastname = $db_participant->last_name;
        $db_tokens->update_attributes( $db_participant );
        $db_tokens->save();

        $this->current_sid = $sid;
        $this->current_token = $token;
      }
      else if( 'N' == $db_tokens->completed )
      {
        $this->current_sid = $sid;
        $this->current_token = $token;
      }
      else // token is complete, store the survey results
      {
        // figure out which token attributes are which
        $db_surveys = lib::create( 'database\limesurvey\surveys', $sid );
        $attributes = array();
        foreach( $db_surveys->get_token_attribute_names() as $key => $value )
          $attributes[$value] = $db_tokens->$key;

        // only worry about participants who have provided data
        if( array_key_exists( 'provided data', $attributes ) &&
            'no' != $attributes['provided data'] )
        {
          if( 0 == $attributes['written consent received'] )
            $letter_type = 0 < $attributes['consented to provide HIN'] ? 'q' : 'r';
          else // written consent was received, write the letter type to the database
          {
            if( 'partial' == $attributes['provided data'] )
              $letter_type = 0 < $attributes['consented to provide HIN'] ? 's' : 't';
            else // full data received
            {
              if( 'comprehensive' == $db_participant->get_cohort()->name && 
                  $attributes['last interview date'] == 'DATE UNKNOWN' ) // in-home only
                $letter_type = 0 < $attributes['consented to provide HIN'] ? 'o' : 'p';
              else // not in-home only
              {
                // from here we need to know whether default was applied or not
                $survey_mod = lib::create( 'database\modifier' );
                $survey_mod->where( 'token', '=', $token );
                $survey_list = $survey_class_name::select( $survey_mod );
                $db_survey = current( $survey_list );
                
                // get the code for the def and opt responses
                $code = 0 < $attributes['consented to provide HIN'] ? 'HIN' : 'NO_HIN';
                $code .= 0 < $attributes['DCS samples'] ? '_SAMP' : '_NO_SAMP';

                $response = array();
                $response['start'] = $db_survey->get_response( 'WTD_START' );
                $response['def'] = $db_survey->get_response( 'WTD_DEF_'.$code );
                $response['opt'] = $db_survey->get_response( 'WTD_OPT_'.$code );

                // the default option was applied if...
                if( 'REFUSED' == $response['start'] ||
                    'YES' == $response['def'] ||
                    'REFUSED' == $response['def'] ||
                    'REFUSED' == $response['opt'] )
                {
                  if( 1 == $attributes['DCS samples'] )
                    $letter_type = 0 < $attributes['consented to provide HIN'] ? 'k' : 'm';
                  else
                    $letter_type = 0 < $attributes['consented to provide HIN'] ? 'l' : 'n';
                }
                else
                {
                  if( 'OPTION1' == $response['opt'] )
                  {
                    if( 1 == $attributes['DCS samples'] )
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'a' : 'c';
                    else
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'b' : 'd';
                  }
                  else if( 'OPTION2' == $response['opt'] )
                  {
                    if( 1 == $attributes['DCS samples'] )
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'e' : 'g';
                    else
                      $letter_type = 0 < $attributes['consented to provide HIN'] ? 'f' : 'h';
                  }
                  else // must be OPTION3
                  {
                    // NOTE: to get option 3 participants must have provided HIN
                    $letter_type = 1 == $attributes['DCS samples'] ? 'i' : 'j';
                  }
                }
              }
            }
          }

          // now write the letter type for future reference
          $db_participant->withdraw_letter = $letter_type;
          $db_participant->save();
        }
      }
      // else do not set the current_sid or current_token members!
    }
    else // we're not running a special interview, so check for an assignment
    {
      $db_assignment = $session->get_current_assignment();
      if( is_null( $db_assignment ) )
      {
        log::warning( 'Tried to determine survey information without an active assignment.' );
        return false;
      }

      // records which we will need
      $db_interview = $db_assignment->get_interview();
      $db_participant = $db_interview->get_participant();
      $db_consent = $db_participant->get_last_consent();
      $db_qnaire = $db_interview->get_qnaire();

      // the participant's last consent is consent, see if the withdraw script is complete
      if( $db_consent && false == $db_consent->accept )
      {
        // let the tokens record class know which SID we are dealing with by checking if
        // there is a source-specific survey for the participant, and if not falling back
        // on the default withdraw survey
        $db_source_withdraw = $source_withdraw_class_name::get_unique_record(
          array( 'qnaire_id', 'source_id' ),
          array( $db_qnaire->id, $db_participant->source_id ) );
        $sid = is_null( $db_source_withdraw ) ? $db_qnaire->withdraw_sid : $db_source_withdraw->sid;

        $tokens_class_name::set_sid( $sid );
        $token = $db_participant->uid;
        $tokens_mod = lib::create( 'database\modifier' );
        $tokens_mod->where( 'token', '=', $token );
        $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );

        if( false === $db_tokens )
        { // token not found, create it
          $db_tokens = lib::create( 'database\limesurvey\tokens' );
          $db_tokens->token = $token;
          $db_tokens->firstname = $db_participant->first_name;
          $db_tokens->lastname = $db_participant->last_name;
          $db_tokens->update_attributes( $db_participant );
          $db_tokens->save();

          $this->current_sid = $sid;
          $this->current_token = $token;
        }
        else if( 'N' == $db_tokens->completed )
        {
          $this->current_sid = $sid;
          $this->current_token = $token;
        }
        // else do not set the current_sid or current_token members!
      }
      else
      { // the participant has not withdrawn, check each phase of the interview
        $phase_mod = lib::create( 'database\modifier' );
        $phase_mod->order( 'rank' );
        
        $phase_list = $db_qnaire->get_phase_list( $phase_mod );
        if( 0 == count( $phase_list ) )
        {
          log::emerg( 'Questionnaire with no phases has been assigned.' );
        }
        else
        {
          foreach( $phase_list as $db_phase )
          {
            // let the tokens record class know which SID we are dealing with by checking if
            // there is a source-specific survey for this participant, and if not falling back
            // on the default survey
            $db_source_survey = $source_survey_class_name::get_unique_record(
              array( 'phase_id', 'source_id' ),
              array( $db_phase->id, $db_participant->source_id ) );
            $sid = is_null( $db_source_survey ) ? $db_phase->sid : $db_source_survey->sid;

            $tokens_class_name::set_sid( $sid );
    
            $token = $tokens_class_name::determine_token_string(
                       $db_interview,
                       $db_phase->repeated ? $db_assignment : NULL );
            $tokens_mod = lib::create( 'database\modifier' );
            $tokens_mod->where( 'token', '=', $token );
            $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );
    
            if( false === $db_tokens )
            { // token not found, create it
              $db_tokens = lib::create( 'database\limesurvey\tokens' );
              $db_tokens->token = $token;
              $db_tokens->firstname = $db_participant->first_name;
              $db_tokens->lastname = $db_participant->last_name;
              $db_tokens->update_attributes( $db_participant );

              // TODO: this is temporary code to fix the TOKEN != "NO" problem in limesurvey
              //       for survey 72154
              if( 72154 == $sid && is_null( $db_tokens->attribute_10 ) )
                $db_tokens->attribute_10 = "UNKNOWN";

              $db_tokens->save();
    
              $this->current_sid = $sid;
              $this->current_token = $token;
              break;
            }
            else if( 'N' == $db_tokens->completed )
            { // we have found the current phase
              $this->current_sid = $sid;
              $this->current_token = $token;
              break;
            }
            // else do not set the current_sid or current_token members!
          }
        }

        // complete the interview if all phases are complete
        if( false === $this->current_sid )
        {
          $db_interview->completed = true;
          $db_interview->save();

          // record the event (if one exists)
          $event_type_name = sprintf( 'completed (%s)', $db_qnaire->name );
          $db_event_type = $event_type_class_name::get_unique_record( 'name', $event_type_name );
          if( !is_null( $db_event_type ) )
          {
            // make sure the event doesn't already exist
            $event_mod = lib::create::create( 'database\modifier' );
            $event_mod->where( 'event_type_id', '=', $db_event_type->id );
            if( 0 == $db_participant->get_event_count( $event_mod ) )
            {
              $db_event = lib::create( 'database\event' );
              $db_event->participant_id = $db_participant->id;
              $db_event->event_type_id = $db_event_type->id;
              $db_event->datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
              $db_event->save();
            }
          }
        }
      }
    }
  }
  
  /**
   * This assignment's current sid
   * @var int
   * @access private
   */
  private $current_sid = NULL;
  
  /**
   * This assignment's current token
   * @var string
   * @access private
   */
  private $current_token = NULL;
}
