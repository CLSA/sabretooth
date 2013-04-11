<?php
/**
 * consent_form_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent form report data.
 * 
 * @abstract
 */
class consent_form_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'consent_form', $args );
  }

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    // get the report args
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );

    // TODO: Change this to the title/code of the limesurvey question to check
    // (this should be the consent form question)
    $question_code = 'A';

    $this->add_title(
      sprintf( 'A list of participant\'s who have indicated they require a new consent form '.
               'during the %s interview', $db_qnaire->name ) );
    
    $contents = array();
    $database_class_name = lib::get_class_name( 'database\participant' );
    $tokens_class_name   = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name   = lib::get_class_name( 'database\limesurvey\survey' );

    // modifiers common to each iteration of the following loops
    $consent_mod = lib::create( 'database\modifier' );
    $consent_mod->where( 'event', '=', 'written accept' );
    $consent_mod->or_where( 'event', '=', 'written deny' );
    $consent_mod->or_where( 'event', '=', 'retract' );
    $consent_mod->or_where( 'event', '=', 'withdraw' );

    // loop through every participant searching for those who have no written consent
    foreach( $database_class_name::select() as $db_participant )
    {
      $done = false;
      if( 0 == count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        // now go through their interviews until the consent question code is found
        $interview_mod = lib::create( 'database\modifier' );
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        foreach( $db_participant->get_interview_list( $interview_mod ) as $db_interview )
        {
          foreach( $db_interview->get_qnaire()->get_phase_list() as $db_phase )
          {
            // figure out the token
            $token = $tokens_class_name::determine_token_string( $db_interview );

            // determine if the participant answered yes to the consent question
            $survey_mod = lib::create( 'database\modifier' );
            if( $db_phase->repeated )
            {
              // replace the token's 0 with a database % wildcard
              $token = substr( $token, 0, -1 ).'%';
              $survey_mod->where( 'token', 'LIKE', $token );
            }
            else $survey_mod->where( 'token', '=', $token );

            $survey_class_name::set_sid( $db_phase->sid );
            foreach( $survey_class_name::select( $survey_mod ) as $db_survey )
            {
              if( $db_survey && 'Y' == $db_survey->get_response( $question_code ) )
              {
                $db_address = $db_participant->get_first_address();
                if( is_null( $db_address ) ) continue;
                $db_region = $db_address->get_region();
                $db_last_phone_call = $db_participant->get_last_contacted_phone_call();

                $contents[] = array(
                  $db_participant->uid,
                  $db_interview->completed ? 'Yes' : 'No',
                  $db_last_phone_call ? $db_last_phone_call->start_datetime : 'never',
                  $db_participant->first_name,
                  $db_participant->last_name,
                  $db_address->address1." ".$db_address->address2,
                  $db_address->city,
                  $db_region->abbreviation,
                  $db_region->country,
                  $db_address->postcode );

                $done = true;
              }
              if( $done ) break; // stop searching if we're done
            }
            if( $done ) break; // stop searching if we're done
          }
          if( $done ) break; // stop searching if we're done
        }
      }
    }
    
    $header = array(
      "UID",
      "Interview\nComplete",
      "Last Contact",
      "First Name",
      "Last Name",
      "Address",
      "City",
      "Prov/State",
      "Country",
      "Postcode" );
    
    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
