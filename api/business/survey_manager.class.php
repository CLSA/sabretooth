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
        $call_list = $db_assignment->get_phone_call_list( $modifier );
        if( 0 != count( $call_list ) )
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
        if( is_null( $db_language ) ) $db_language = $session->get_service()->get_language();
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

    if( 'cohort' == $key )
    {
      $value = $db_participant->get_cohort()->name;
    }
    else if( 1 == preg_match( '/^collection./', $key ) )
    {
      $parts = explode( '.', $key );
      if( 2 != count( $parts ) )
        throw lib::create( 'exception\argument', 'key', $key, __METHOD__ );

      $collection_name = $parts[1];
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'collection.name', '=', $collection_name );
      $value = 0 < $db_participant->get_collection_count( $modifier ) ? 1 : 0;
    }
    else if( 'phone.1.number' == $key )
    {
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'rank', '=', 1 );
      $phone_list = $db_participant->get_phone_list( $modifier );
      $db_phone = current( $phone_list );
      $value = is_null( $db_phone ) ? '' : $db_phone->number;
    }
    else if( 'uid' == $key )
    {
      $value = $db_participant->uid;
    }
    else if( 'site' == $key )
    {
      $db_site = $db_participant->get_effective_site();
      $value = is_null( $db_site ) ? 'none' : $db_site->name;
    }
    else if( 'override quota' == $key )
    {
      // override_quota is true if the participant's quota is disabled AND override_quota is true
      $override_quota = '0';
      $value = false === $db_participant->get_quota_enabled() &&
               ( $db_participant->override_quota || $db_participant->get_source()->override_quota )
             ? '1' 
             : '0';
    }
    else if( false !== strpos( $key, 'address' ) )
    {
      $db_address = $db_participant->get_primary_address();
      
      if( 'address street' == $key )
      {
        if( $db_address )
        {
          $value = $db_address->address1;
          if( !is_null( $db_address->address2 ) ) $value .= ' '.$db_address->address2;
        }
        else
        {
          $value = '';
        }
      }
      else if( 'address city' == $key )
      {
        $value = $db_address ? $db_address->city : '';
      }
      else if( 'address province' == $key )
      {
        $value = $db_address ? $db_address->get_region()->name : '';
      }
      else if( 'address postal code' == $key )
      {
        $value = $db_address ? $db_address->postcode : '';
      }
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
      $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );
      
      $db_phase = current( $phase_list );
      if( $db_phase && 1 == $db_interview->get_assignment_count() )
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
    else if( 'written consent received' == $key )
    {
      $consent_mod = lib::create( 'database\modifier' );
      $consent_mod->where( 'written', '=', true );
      $value = 0 < $db_participant->get_consent_count( $consent_mod ) ? '1' : '0';
    }
    else if( 'consented to provide HIN' == $key )
    {
      $db_hin = $db_participant->get_hin();
      if( is_null( $db_hin ) ) $value = -1;
      else $value = 1 == $db_hin->access ? 1 : 0;
    }
    else if( 'HIN recorded' == $key )
    {
      $db_hin = $db_participant->get_hin();
      $value = !( is_null( $db_hin ) || is_null( $db_hin->code ) );
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
        
        $event_list = $db_participant->get_event_list( $event_mod );
        $provided_data = 0 < count( $event_list ) ? 'yes' : 'no';
      }
      else
      {
        $provided_data = 'no';

        // start by seeing if the participant has completed the baseline interview
        $event_mod = lib::create( 'database\modifier' );
        $event_mod->where( 'event_type_id', '=',
          $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );
        
        $event_list = $db_participant->get_event_list( $event_mod );
        if( 0 < count( $event_list ) ) $provided_data = 'yes';
        else
        { // if the interview was never completed, see if it was partially completed
          $interview_mod = lib::create( 'database\modifier' );
          $interview_mod->order( 'qnaire.rank' );
          $interview_list = $db_participant->get_interview_list( $interview_mod );
          if( 0 < count( $interview_list ) )
          {
            $phase_mod = lib::create( 'database\modifier' );
            $phase_mod->where( 'repeated', '=', 0 );
            $phase_mod->order( 'rank' );
            $db_last_interview = current( $interview_list );
            $phase_list = $db_last_interview->get_qnaire()->get_phase_list( $phase_mod );
            if( 0 < count( $phase_list ) )
            {
              $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
              $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

              // see if a survey exists for this phase
              // if one does then the participant has provided partial data
              $db_phase = current( $phase_list );
              $survey_class_name::set_sid( $db_phase->sid );
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
    else if( false !== strpos( $key, 'marital status' ) )
    {
      // get data from Opal
      $setting_manager = lib::create( 'business\setting_manager' );
      $opal_url = $setting_manager->get_setting( 'opal', 'server' );
      $opal_manager = lib::create( 'business\opal_manager', $opal_url );
      
      $value = 'MISSING';

      if( $opal_manager->get_enabled() )
      {
        try
        {
          $db_cohort = $db_participant->get_cohort();
          $datasource = 'comprehensive' == $db_cohort->name ? 'clsa-inhome' : 'clsa-cati';
          $table = 'comprehensive' == $db_cohort->name
                 ? 'InHome_1'
                 : 'Tracking Baseline Main Script';
          $variable = 'comprehensive' == $db_cohort->name ? 'SDC_MRTL_COM' : 'SDC_MRTL_TRM';
          $value = $opal_manager->get_value( $datasource, $table, $db_participant, $variable );

          // return the label instead of the value, if requested
          if( 'marital status label' == $key )
            $value = $opal_manager->get_label(
              $datasource, $table, $variable, $value, $db_participant->get_language() );
        }
        catch( \cenozo\exception\base_exception $e )
        {
          // ignore argument exceptions (data not found in Opal) and report the rest
          if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
        }
      }
    }
    else if( 'parkinsonism' == $key )
    {
      // get data from Opal
      $setting_manager = lib::create( 'business\setting_manager' );
      $opal_url = $setting_manager->get_setting( 'opal', 'server' );
      $opal_manager = lib::create( 'business\opal_manager', $opal_url );
      
      $value = 'NO';

      if( $opal_manager->get_enabled() )
      {
        try
        {
          $db_cohort = $db_participant->get_cohort();
          $datasource = 'comprehensive' == $db_cohort->name ? 'clsa-dcs' : 'clsa-cati';
          $table = 'comprehensive' == $db_cohort->name
                 ? 'DiseaseSymptoms'
                 : 'Tracking Baseline Main Script';
          $variable = 'comprehensive' == $db_cohort->name ? 'CCC_PARK_DCS' : 'CCT_PARK_TRM';
          $value = $opal_manager->get_value(
            $datasource, $table, $db_participant, $variable );
        }
        catch( \cenozo\exception\base_exception $e )
        {
          // ignore argument exceptions (data not found in Opal) and report the rest
          if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
        }
      }
    }
    else if( 1 == preg_match( '/^(INT|INCL)_/', $key ) )
    {
      $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
      $source_survey_class_name = lib::get_class_name( 'database\source_survey' );
      
      if( is_null( $db_interview ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Can\'t provide survey attribute "%s" without an interview record', $key ),
          __METHOD__ );

      $phase_mod = lib::create( 'database\modifier' );
      $phase_mod->where( 'rank', '=', 1 );
      $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );

      // determine the SID of the first phase of the questionnaire (where the question is asked)
      if( 1 == count( $phase_list ) )
      {
        $db_phase = current( $phase_list );
        $db_source_survey = $source_survey_class_name::get_unique_record(
          array( 'phase_id', 'source_id' ),
          array( $db_phase->id, $db_participant->source_id ) );
        $survey_class_name::set_sid(
          is_null( $db_source_survey ) ? $db_phase->sid : $db_source_survey->sid );

        $survey_mod = lib::create( 'database\modifier' );
        $survey_mod->where( 'token', 'LIKE', $db_interview->id.'_%' );
        $survey_mod->order_desc( 'datestamp' );
        $survey_list = $survey_class_name::select( $survey_mod );

        $found = false;
        foreach( $survey_list as $db_survey )
        { // loop through all surveys until an answer is found
          try
          {
            $value = $db_survey->get_response( $key );
            // INT_13a matches any survey response, others match any NON NULL response
            if( 'INT_13a' == $key || !is_null( $value ) ) $found = true;
          }
          catch( \cenozo\exception\runtime $e )
          {
            // ignore the error and continue without setting the attribute
          }
          
          if( $found ) break;
        }
      }
    }
    else if( 'operator first_name' == $key )
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $value = $db_user->first_name;
    }
    else if( 'operator last_name' == $key )
    {
      $db_user = lib::create( 'business\session' )->get_user();
      $value = $db_user->last_name;
    }
    else if( 'participant_source' == $key )
    {
      $db_source = $db_participant->get_source();
      $value = is_null( $db_source ) ? '(none)' : $db_source->name;
    }
    else if( 'previous CCHS contact date' == $key )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );
      $datetime_list = $db_participant->get_event_datetime_list(
        $event_type_class_name::get_unique_record( 'name', 'completed pilot interview' ) );
      $value = 0 < count( $datetime_list ) ? current( $datetime_list ) : NULL;
    }
    else if( 'last interview date' == $key )
    {
      $event_type_class_name = lib::get_class_name( 'database\event_type' );
      $event_mod = lib::create( 'database\modifier' );
      $event_mod->order_desc( 'datetime' );
      $event_mod->where_bracket( true );
      $event_mod->where( 'event_type_id', '=',
        $event_type_class_name::get_unique_record( 'name', 'completed (Baseline)' )->id );
      $event_mod->or_where( 'event_type_id', '=',
        $event_type_class_name::get_unique_record( 'name', 'completed (Baseline Site)' )->id );
      $event_mod->where_bracket( false );
      
      $event_list = $db_participant->get_event_list( $event_mod );
      $db_event = 0 < count( $event_list ) ? current( $event_list ) : NULL;
      $value = is_null( $db_event )
                  ? 'DATE UNKNOWN'
                  : util::get_formatted_date( $db_event->datetime );
    }
    else if( false !== strpos( $key, 'alternate' ) )
    {
      $alternate_list = $db_participant->get_alternate_list();

      $matches = array(); // for pregs below
      if( 'number of alternate contacts' == $key )
      {
        $value = count( $alternate_list );
      }
      else if(
        preg_match( '/alternate([0-9]+) (first_name|last_name|phone)/', $key, $matches ) )
      {
        $alt_number = intval( $matches[1] );
        $aspect = $matches[2];

        if( count( $alternate_list ) < $alt_number )
        {
          $value = '';
        }
        else
        {
          if( 'phone' == $aspect )
          {
            $phone_list = $alternate_list[$alt_number - 1]->get_phone_list();
            $value = is_array( $phone_list ) ? $phone_list[0]->number : '';
          }
          else
          {
            $value = $alternate_list[$alt_number - 1]->$aspect;
          }
        }
      }
    }
    else if( preg_match( '/secondary (first_name|last_name)/', $key ) )
    {
      $aspect = str_replace( ' ', '_', $key );
      if( array_key_exists( $aspect, $_COOKIE ) ) $value = $_COOKIE[$aspect];
    }
    else if( 'previously completed' == $key )
    {
      if( is_null( $db_interview ) )
        throw lib::create( 'exception\runtime',
          sprintf( 'Can\'t provide survey attribute "%s" without an interview record', $key ),
          __METHOD__ );

      $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );

      // no need to set the token sid since it should already be set before calling this method
      $tokens_mod = lib::create( 'database\modifier' );
      $tokens_mod->where( 'token', 'like', $db_interview->id.'_%' );
      $tokens_mod->where( 'completed', '!=', 'N' );
      $value = $tokens_class_name::count( $tokens_mod );
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
