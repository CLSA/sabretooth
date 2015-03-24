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

    // determine the participant
    $db_participant = NULL;
    if( array_key_exists( 'secondary_id', $_COOKIE ) )
    {
      $db_participant = lib::create( 'database\participant', $_COOKIE['secondary_participant_id'] );
    }
    else if( array_key_exists( 'withdrawing_participant', $_COOKIE ) )
    {
      $db_participant = lib::create( 'database\participant', $_COOKIE['withdrawing_participant'] );
    }
    else if( 'operator' == $session->get_role()->name )
    {
      // must have an assignment
      $db_assignment = $session->get_current_assignment();
      if( !is_null( $db_assignment ) )
      {
        // the assignment must have an open call
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'end_datetime', '=', NULL );
        if( 0 < $db_assignment->get_phone_call_count( $modifier ) )
          $db_participant = $db_assignment->get_interview()->get_participant();
      }
    }

    if( !is_null( $db_participant ) )
    {
      $sid = $this->get_current_sid();
      $token = $this->get_current_token();
      if( false !== $sid && false != $token )
      {
        // determine which language to use
        $db_language = $db_participant->get_language();
        if( is_null( $db_language ) ) $db_language = $session->get_application()->get_language();
        return sprintf( '%s/index.php?sid=%s&lang=%s&token=%s&newtest=Y',
                        LIMESURVEY_URL,
                        $sid,
                        $db_language->code,
                        $token );
      }
    }

    // there is currently no active survey
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
    $source_survey_class_name = lib::get_class_name( 'database\source_survey' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

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

      $db_assignment = $db_participant->get_current_assignment();
      if( is_null( $db_assignment ) )
        $db_assignment = $db_participant->get_last_finished_assignment();
      $db_interview = is_null( $db_assignment ) ? NULL : $db_assignment->get_interview();

      $sid = $setting_manager->get_setting( 'general', 'secondary_survey' );
      $token = $_COOKIE['secondary_id'];

      $tokens_class_name::set_sid( $sid );
      $survey_class_name::set_sid( $sid );

      // reset the script and token
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', '=', $token );
      foreach( $tokens_class_name::select( $tokens_mod ) as $db_tokens ) $db_tokens->delete();
      $survey_mod = lib::create( 'database\modifier' );
      $survey_mod->where( 'token', '=', $token );
      foreach( $survey_class_name::select( $survey_mod ) as $db_survey ) $db_survey->delete();

      $db_tokens = lib::create( 'database\limesurvey\tokens' );
      $db_tokens->token = $token;
      $db_tokens->firstname = $db_participant->first_name;
      $db_tokens->lastname = $db_participant->last_name;
      $db_tokens->email = $db_participant->email;

      if( 0 < strlen( $db_participant->other_name ) )
        $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

      // fill in the attributes
      $db_surveys = lib::create( 'database\limesurvey\surveys', $sid );
      foreach( $db_surveys->get_token_attribute_names() as $key => $value )
        $db_tokens->$key = static::get_attribute( $db_participant, $db_interview, $value );

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

      $this->process_withdraw( $db_participant );
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

      // the participant's last consent is consent, see if the withdraw script is complete
      if( $db_consent && false == $db_consent->accept )
      {
        // the rest is done in a private method
        $this->process_withdraw( $db_participant );
      }
      else
      { // the participant has not withdrawn, check each phase of the interview
        $db_qnaire = $db_interview->get_qnaire();
        $phase_sel = lib::create( 'database\select' );
        $phase_sel->add_column( 'id' );
        $phase_sel->add_column( 'sid' );
        $phase_sel->add_column( 'repeated' );
        $phase_mod = lib::create( 'database\modifier' );
        $phase_mod->order( 'rank' );
        
        $phase_list = $db_qnaire->get_phase_list( $phase_sel, $phase_mod );
        if( 0 == count( $phase_list ) )
        {
          log::emerg( 'Questionnaire with no phases has been assigned.' );
        }
        else
        {
          foreach( $phase_list as $phase )
          {
            // let the tokens record class know which SID we are dealing with by checking if
            // there is a source-specific survey for this participant, and if not falling back
            // on the default survey
            $db_source_survey = $source_survey_class_name::get_unique_record(
              array( 'phase_id', 'source_id' ),
              array( $phase['id'], $db_participant->source_id ) );
            $sid = is_null( $db_source_survey ) ? $phase['sid'] : $db_source_survey->sid;

            $tokens_class_name::set_sid( $sid );
    
            $token = $tokens_class_name::determine_token_string(
                       $db_interview,
                       $phase['repeated'] ? $db_assignment : NULL );
            $tokens_mod = lib::create( 'database\modifier' );
            $tokens_mod->where( 'token', '=', $token );
            $db_tokens = current( $tokens_class_name::select( $tokens_mod ) );
    
            if( false === $db_tokens )
            { // token not found, create it
              $db_tokens = lib::create( 'database\limesurvey\tokens' );
              $db_tokens->token = $token;
              $db_tokens->firstname = $db_participant->first_name;
              $db_tokens->lastname = $db_participant->last_name;
              $db_tokens->email = $db_participant->email;

              if( 0 < strlen( $db_participant->other_name ) )
                $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

              // fill in the attributes
              $db_surveys = lib::create( 'database\limesurvey\surveys', $sid );
              foreach( $db_surveys->get_token_attribute_names() as $key => $value )
                $db_tokens->$key = static::get_attribute( $db_participant, $db_interview, $value );

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

        // complete the interview and update the recording list if all phases are complete
        if( false === $this->current_sid ) $db_interview->complete();
      }
    }
  }

  /**
   * Internal method to handle the withdraw script
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant
   * @access private
   */
  private function process_withdraw( $db_participant )
  {
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

    $withdraw_manager = lib::create( 'business\withdraw_manager' );

    // let the tokens record class know which SID we are dealing with by checking if
    // there is a source-specific survey for the participant, and if not falling back
    // on the default withdraw survey
    $withdraw_sid = $withdraw_manager->get_withdraw_sid( $db_participant );
    if( is_null( $withdraw_sid ) )
      throw lib::create( 'exception\runtime',
        sprintf( 'Trying to withdraw participant %s without a withdraw survey.',
                 $db_participant->uid ),
        __METHOD__ );
    $db_surveys = lib::create( 'database\limesurvey\surveys', $withdraw_sid );

    $tokens_class_name::set_sid( $withdraw_sid );
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
      $db_tokens->email = $db_participant->email;

      if( 0 < strlen( $db_participant->other_name ) )
        $db_tokens->firstname .= sprintf( ' (%s)', $db_participant->other_name );

      // fill in the attributes
      foreach( $db_surveys->get_token_attribute_names() as $key => $value )
        $db_tokens->$key = static::get_attribute( $db_participant, NULL, $value );

      $db_tokens->save();

      $this->current_sid = $withdraw_sid;
      $this->current_token = $token;
    }
    else if( 'N' == $db_tokens->completed )
    {
      $this->current_sid = $withdraw_sid;
      $this->current_token = $token;
    }
    else // token is complete, store the survey results
    {
      $withdraw_manager->process( $db_participant );
    }
  }

  /**
   * Determines attributes needed at survey time.
   * TODO: this method contains many reference to CLSA-specific features which
   *       should be made generic
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant
   * @param database\interview $db_interview The participant's current interview (may be set to null)
   * @param string $key The name of the attribute to return.
   * @return mixed
   * @access public
   */
  public static function get_attribute( $db_participant, $db_interview, $key )
  {
    $value = NULL;

    if( false !== strpos( $key, '.' ) )
    { // key contains a '.', use new style attribute
      $data_manager = lib::create( 'business\data_manager' );
      $value = 0 === strpos( $key, 'participant\.' )
             ? $data_manager->get_participant_value( $db_participant, $key )
             : $data_manager->get_value( $key );
    }
    else if( 'age' == $key )
    {
      // if this is the participant's first assignment copy the date of birth from Opal
      // (if it exists)
      if( is_null( $db_interview ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Can\'t provide survey attribute "%s" without an interview record', $key ),
          __METHOD__ );

      $phase_mod = lib::create( 'database\modifier' );
      $phase_mod->where( 'rank', '=', 1 );
      if( 0 < $db_interview->get_qnaire()->get_phase_count( $phase_mod ) &&
          1 == $db_interview->get_assignment_count() )
      {
        $setting_manager = lib::create( 'business\setting_manager' );
        $opal_url = $setting_manager->get_setting( 'opal', 'server' );
        $opal_manager = lib::create( 'business\opal_manager', $opal_url );
        
        if( $opal_manager->get_enabled() )
        {
          try
          {
            $db_cohort = $db_cohort = $db_participant->get_cohort();
            $datasource = 'comprehensive' == $db_cohort->name ? 'clsa-inhome' : 'clsa-cati';
            $table = 'comprehensive' == $db_cohort->name
                   ? 'InHome_Id'
                   : 'Tracking Baseline Main Script';
            $variable = 'comprehensive' == $db_cohort->name ? 'AGE_DOB_AGE_COM' : 'AGE_DOB_TRM';
            $dob = $opal_manager->get_value( $datasource, $table, $db_participant, $variable );
            
            if( $dob )
            { // only write the date of birth if there is one
              try
              {
                $dob_obj = util::get_datetime_object( $dob );
                if( 1965 >= intval( $dob_obj->format( 'Y' ) ) )
                { // only accept dates of birth on or before 1965
                  $db_participant->date_of_birth = $dob;
                  $db_participant->save();
                }
              }
              catch( \Exception $e ) {} 
            }
          }
          catch( \cenozo\exception\base_exception $e )
          {
            // ignore argument exceptions (data not found in Opal) and report the rest
            if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
          }
        }
      }

      $value = strlen( $db_participant->date_of_birth )
                  ? util::get_interval(
                      util::get_datetime_object( $db_participant->date_of_birth ) )->y
                  : '';
    }
    else if( 'provided data' == $key )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );

      if( 'comprehensive' == $db_participant->get_cohort()->name )
      {
        // comprehensive participants have provided data once their first interview is done
        $event_mod = lib::create( 'database\modifier' );
        $event_mod->where( 'event_type_id', '=',
          $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Home)' )->id );
        $provided_data = 0 < $db_participant->get_event_count( $event_mod ) ? 'yes' : 'no';
      }
      else
      {
        $provided_data = 'no';

        // start by seeing if the participant has completed the baseline interview
        $event_mod = lib::create( 'database\modifier' );
        $event_mod->where( 'event_type_id', '=',
          $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );
        
        if( 0 < $db_participant->get_event_count( $event_mod ) ) $provided_data = 'yes';
        else
        { // if the interview was never completed, see if it was partially completed
          $interview_mod = lib::create( 'database\modifier' );
          $interview_mod->order( 'qnaire.rank' );
          $interview_mod->limit( 1 );
          $interview_list = $db_participant->get_interview_object_list( $interview_mod );
          if( 0 < count( $interview_list ) )
          {
            $db_last_interview = current( $interview_list );
            $phase_sel = lib::create( 'database\select' );
            $phase_sel->add_column( 'sid' );
            $phase_mod = lib::create( 'database\modifier' );
            $phase_mod->where( 'repeated', '=', 0 );
            $phase_mod->order( 'rank' );
            $phase_list = $db_last_interview->get_qnaire()->get_phase_list( $phase_sel, $phase_mod );
            if( 0 < count( $phase_list ) )
            {
              $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
              $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

              // see if a survey exists for this phase
              // if one does then the participant has provided partial data
              $survey_class_name::set_sid( $phase_list[0]['sid'] );
              $survey_mod = lib::create( 'database\modifier' );
              $survey_mod->where( 'token', '=',
                $tokens_class_name::determine_token_string( $db_last_interview ) );
              if( 0 < $survey_class_name::count( $survey_mod ) ) $provided_data = 'partial';
            }
          }
        }
      }

      $value = $provided_data;
    }
    else if( 'DCS samples' == $key )
    {
      // get data from Opal
      $setting_manager = lib::create( 'business\setting_manager' );
      $opal_url = $setting_manager->get_setting( 'opal', 'server' );
      $opal_manager = lib::create( 'business\opal_manager', $opal_url );
      
      $value = 0;

      if( $opal_manager->get_enabled() && 'comprehensive' == $db_participant->get_cohort()->name )
      {
        try
        {
          $blood = $opal_manager->get_value(
            'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_BS' );
          $urine = $opal_manager->get_value(
            'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_URINE' );

          $value = 0 == strcasecmp( 'yes', $blood ) ||
                        0 == strcasecmp( 'yes', $urine )
                      ? 1 : 0;
        }
        catch( \cenozo\exception\base_exception $e )
        {
          // ignore argument exceptions (data not found in Opal) and report the rest
          if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
        }
      }
    }

    return $value;
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
