<?php
/**
 * mailout_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Mailout required report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class mailout_required_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'mailout_required', $args );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get the report arguments
    $mailout_type = $this->get_argument( 'restrict_mailout_type' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );

    // TODO: Change this to the title/code of the limesurvey question to check
    // (this should be the new information package required question)

    if( $mailout_type == 'Participant information package' )
    {
      $question_code = 'A';
      $title = 'Participant Information Package Required Report';
    }
    else
    {
      $question_code = 'B';
      $title = 'Proxy Information Package Required Report';
    }

    $this->add_title( 
      sprintf( 'Listing of those who requested a new information package during '.
               'the %s interview', $db_qnaire->name ) ) ;
    
    // modifiers common to each iteration of the following loops
    $consent_mod = lib::create( 'database\modifier' );
    $consent_mod->where( 'event', '=', 'verbal accept' );
    $consent_mod->or_where( 'event', '=', 'written accept' );

    $contents = array();
    $tokens_class_name = lib::get_class_name( 'database\limesurvey\tokens' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );

    // loop through participants searching for those who have completed their most recent interview
    $participant_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $participant_mod->where( 'participant_site.site_id', '=', $restrict_site_id );
    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      $done = false;

      if( !is_null( $db_participant->status ) ) continue;      

      if( count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        $interview_mod = lib::create( 'database\modifier' );
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $db_participant->get_interview_list( $interview_mod );
        $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
        if( $db_interview && $db_interview->completed )
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
                $date_completed = 'NA';
                if( !is_null( $db_last_phone_call ) )
                {
                  $date_completed = substr( $db_last_phone_call->start_datetime, 0, 
                    strpos( $db_last_phone_call->start_datetime, ' ' ) );
                }

                $contents[] = array(
                  $db_participant->uid,
                  $db_participant->first_name,
                  $db_participant->last_name,
                  $db_address->address1,
                  $db_address->city,
                  $db_region->abbreviation,
                  $db_region->country,
                  $db_address->postcode,
                  $date_completed );

                $done = true;
              }
              if( $done ) break; // stop searching if we're done
            } // end loop on survey
            if( $done ) break; // stop searching if we're done
          } // end loop on qnaire phases
          if( $done ) break; // stop searching if we're done
        } // end if completed interview
      } // end if verbal or written consent obtained
    }// end participant loop 
    
    $header = array(
      "UID",
      "First Name",
      "Last Name",
      "Address",
      "City",
      "Prov/State",
      "Country",
      "Postcode",
      "Date Completed" );
    
    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
