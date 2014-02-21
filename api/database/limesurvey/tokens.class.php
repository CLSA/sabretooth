<?php
/**
 * tokens.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Access to limesurvey's tokens_SID tables.
 */
class tokens extends sid_record
{
  /**
   * Updates the token attributes with current values from Mastodon
   * TODO: this method contains many reference to CLSA-specific features which
   *       should be made generic
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant The record of the participant linked to this token.
   * @param boolean $extended Whether or not to included extended parameters.
   * @access public
   */
  public function update_attributes( $db_participant )
  {
    if( NULL == $this->token )
    {
      log::warning( 'Tried to update attributes of token without token string.' );
      return;
    }

    $db_user = lib::create( 'business\session' )->get_user();
    $db_cohort = $db_participant->get_cohort();

    // determine the first part of the token
    $token_part = substr( $this->token, 0, strpos( $this->token, '_' ) + 1 );
    
    // fill in the email
    $this->email = $db_participant->email;
    
    // determine the attributes from the survey with the same ID
    $db_surveys = lib::create( 'database\limesurvey\surveys', static::get_sid() );

    foreach( $db_surveys->get_token_attribute_names() as $key => $value )
    {
      $matches = array(); // for pregs below
      
      // now get the info based on the attribute name
      if( 'cohort' == $value )
      {
        $this->$key = $db_cohort->name;
      }
      else if( 'uid' == $value )
      {
        $this->$key = $db_participant->uid;
      }
      else if( 'override quota' == $value )
      {
        // override_quota is true if the participant's quota is disabled AND
        // their override_quota is true
        $override_quota = '0';
        $db_quota = $db_participant->get_quota();
        $this->$key = !is_null( $db_quota ) && 
                      $db_quota->state_disabled &&
                      ( $db_participant->override_quota ||
                        $db_participant->get_source()->override_quota )
                    ? '1' 
                    : '0';
      }
      else if( false !== strpos( $value, 'address' ) )
      {
        $db_address = $db_participant->get_primary_address();
        
        if( 'address street' == $value )
        {
          if( $db_address )
          {
            $this->$key = $db_address->address1;
            if( !is_null( $db_address->address2 ) ) $this->$key .= ' '.$db_address->address2;
          }
          else
          {
            $this->$key = '';
          }
        }
        else if( 'address city' == $value )
        {
          $this->$key = $db_address ? $db_address->city : '';
        }
        else if( 'address province' == $value )
        {
          $this->$key = $db_address ? $db_address->get_region()->name : '';
        }
        else if( 'address postal code' == $value )
        {
          $this->$key = $db_address ? $db_address->postcode : '';
        }
      }
      else if( 'age' == $value )
      {
        // if this is the participant's first assignment copy the date of birth from Opal
        // (if it exists)
        $db_interview = lib::create( 'business\session')->get_current_assignment()->get_interview();
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

        $this->$key = strlen( $db_participant->date_of_birth )
                    ? util::get_interval(
                        util::get_datetime_object( $db_participant->date_of_birth ) )->y
                    : '';
      }
      else if( 'written consent received' == $value )
      {
        $consent_mod = lib::create( 'database\modifier' );
        $consent_mod->where( 'written', '=', true );
        $this->$key = 0 < $db_participant->get_consent_count( $consent_mod ) ? '1' : '0';
      }
      else if( 'consented to provide HIN' == $value )
      {
        $db_hin = $db_participant->get_hin();
        if( is_null( $db_hin ) ) $this->$key = -1;
        else $this->$key = 1 == $db_hin->access ? 1 : 0;
      }
      else if( 'HIN recorded' == $value )
      {
        $db_hin = $db_participant->get_hin();
        $this->$key = !( is_null( $db_hin ) || is_null( $db_hin->code ) );
      }
      else if( 'provided data' == $value )
      {
        $event_type_class_name = lib::get_class_name( 'database\event_type' );

        if( 'comprehensive' == $db_cohort->name )
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
              $db_interview = current( $interview_list );
              $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );
              if( 0 < count( $phase_list ) )
              {
                $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );

                // see if a survey exists for this phase
                // if one does then the participant has provided partial data
                $db_phase = current( $phase_list );
                $survey_class_name::set_sid( $db_phase->sid );
                $survey_mod = lib::create( 'database\modifier' );
                $survey_mod->where( 'token', '=',
                  static::determine_token_string( $db_interview ) );
                if( 0 < $survey_class_name::count( $survey_mod ) ) $provided_data = 'partial';
              }
            }
          }
        }

        $this->$key = $provided_data;
      }
      else if( 'DCS samples' == $value )
      {
        // get data from Opal
        $setting_manager = lib::create( 'business\setting_manager' );
        $opal_url = $setting_manager->get_setting( 'opal', 'server' );
        $opal_manager = lib::create( 'business\opal_manager', $opal_url );
        
        $this->$key = 0;

        if( $opal_manager->get_enabled() && 'comprehensive' == $db_cohort->name )
        {
          try
          {
            $blood = $opal_manager->get_value(
              'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_BS' );
            $urine = $opal_manager->get_value(
              'clsa-dcs', 'Phlebotomy', $db_participant, 'AGREE_URINE' );

            $this->$key = 0 == strcasecmp( 'yes', $blood ) ||
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
      else if( 'marital status' == $value )
      {
        // get data from Opal
        $setting_manager = lib::create( 'business\setting_manager' );
        $opal_url = $setting_manager->get_setting( 'opal', 'server' );
        $opal_manager = lib::create( 'business\opal_manager', $opal_url );
        
        $this->$key = 'MISSING';

        if( $opal_manager->get_enabled() )
        {
          try
          {
            $datasource = 'comprehensive' == $db_cohort->name ? 'clsa-inhome' : 'clsa-cati';
            $table = 'comprehensive' == $db_cohort->name
                   ? 'InHome_1'
                   : 'Tracking Baseline Main Script';
            $variable = 'comprehensive' == $db_cohort->name ? 'SDC_MRTL_COM' : 'SDC_MRTL_TRM';
            $this->$key = $opal_manager->get_value(
              $datasource, $table, $db_participant, $variable );
          }
          catch( \cenozo\exception\base_exception $e )
          {
            // ignore argument exceptions (data not found in Opal) and report the rest
            if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
          }
        }
      }
      else if( 'parkinsonism' == $value )
      {
        // get data from Opal
        $setting_manager = lib::create( 'business\setting_manager' );
        $opal_url = $setting_manager->get_setting( 'opal', 'server' );
        $opal_manager = lib::create( 'business\opal_manager', $opal_url );
        
        $this->$key = 'NO';

        if( $opal_manager->get_enabled() )
        {
          try
          {
            $datasource = 'comprehensive' == $db_cohort->name ? 'clsa-dcs' : 'clsa-cati';
            $table = 'comprehensive' == $db_cohort->name
                   ? 'DiseaseSymptoms'
                   : 'Tracking Baseline Main Script';
            $variable = 'comprehensive' == $db_cohort->name ? 'CCC_PARK_DCS' : 'CCT_PARK_TRM';
            $this->$key = $opal_manager->get_value(
              $datasource, $table, $db_participant, $variable );
          }
          catch( \cenozo\exception\base_exception $e )
          {
            // ignore argument exceptions (data not found in Opal) and report the rest
            if( 'argument' != $e->get_type() ) log::warning( $e->get_message() );
          }
        }
      }
      else if( 1 == preg_match( '/^(INT|INCL)_/', $value ) )
      {
        $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
        $source_survey_class_name = lib::get_class_name( 'database\source_survey' );
        
        $db_interview = lib::create( 'business\session')->get_current_assignment()->get_interview();
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
          $survey_mod->where( 'token', 'LIKE', $token_part.'%' );
          $survey_mod->order_desc( 'datestamp' );
          $survey_list = $survey_class_name::select( $survey_mod );

          $found = false;
          foreach( $survey_list as $db_survey )
          { // loop through all surveys until an answer is found
            try
            {
              $this->$key = $db_survey->get_response( $value );
              // INT_13a matches any survey response, others match any NON NULL response
              if( 'INT_13a' == $value || !is_null( $this->$key ) ) $found = true;
            }
            catch( \cenozo\exception\runtime $e )
            {
              // ignore the error and continue without setting the attribute
            }
            
            if( $found ) break;
          }
        }
      }
      else if( 'operator first_name' == $value )
      {
        $this->$key = $db_user->first_name;
      }
      else if( 'operator last_name' == $value )
      {
        $this->$key = $db_user->last_name;
      }
      else if( 'participant_source' == $value )
      {
        $db_source = $db_participant->get_source();
        $this->$key = is_null( $db_source ) ? '(none)' : $db_source->name;
      }
      else if( 'previous CCHS contact date' == $value )
      {
        $event_type_class_name = lib::get_class_name( 'database\event_type' );
        $datetime_list = $db_participant->get_event_datetime_list(
          $event_type_class_name::get_unique_record( 'name', 'completed pilot interview' ) );
        $this->$key = 0 < count( $datetime_list ) ? current( $datetime_list ) : NULL;
      }
      else if( 'last interview date' == $value )
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
        $this->$key = is_null( $db_event )
                    ? 'DATE UNKNOWN'
                    : util::get_formatted_date( $db_event->datetime );
      }
      else if( false !== strpos( $value, 'alternate' ) )
      {
        $alternate_list = $db_participant->get_alternate_list();

        if( 'number of alternate contacts' == $value )
        {
          $this->$key = count( $alternate_list );
        }
        else if(
          preg_match( '/alternate([0-9]+) (first_name|last_name|phone)/', $value, $matches ) )
        {
          $alt_number = intval( $matches[1] );
          $aspect = $matches[2];

          if( count( $alternate_list ) < $alt_number )
          {
            $this->$key = '';
          }
          else
          {
            if( 'phone' == $aspect )
            {
              $phone_list = $alternate_list[$alt_number - 1]->get_phone_list();
              $this->$key = is_array( $phone_list ) ? $phone_list[0]->number : '';
            }
            else
            {
              $this->$key = $alternate_list[$alt_number - 1]->$aspect;
            }
          }
        }
      }
      else if( preg_match( '/secondary (first_name|last_name)/', $value ) )
      {
        $aspect = str_replace( ' ', '_', $value );
        if( array_key_exists( $aspect, $_COOKIE ) ) $this->$key = $_COOKIE[$aspect];
      }
      else if( 'previously completed' == $value )
      {
        // no need to set the token sid since it should already be set before calling this method
        $tokens_mod = lib::create( 'database\modifier' );
        $tokens_mod->where( 'token', 'like', $token_part.'%' );
        $tokens_mod->where( 'completed', '!=', 'N' );
        $this->$key = static::count( $tokens_mod );
      }
    }
  }

  /**
   * Returns the token name for a particular interview.
   * If the survey's phase is repeated then the assignment must also be provided.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\interview $db_interview 
   * @param database\interview $db_assignment (only used if the phase is repeated)
   * @static
   * @access public
   */
  public static function determine_token_string( $db_interview, $db_assignment = NULL )
  {
    return sprintf( '%s_%s',
                    $db_interview->id,
                    is_null( $db_assignment ) ? 0 : $db_assignment->id );
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'tid';
}
