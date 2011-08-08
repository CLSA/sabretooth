<?php
/**
 * mailout_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Mailout required report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class mailout_required_report extends base_report
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

  public function finish()
  {
    // get the report arguments
    $mailout_type = $this->get_argument( 'mailout_type' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $db_qnaire = new db\qnaire( $this->get_argument( 'qnaire_id' ) );

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

    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_site->name;
    }
    $this->add_title( $title );
    $this->add_title( 
      sprintf( 'Listing of those who requested a new information package during '.
               'the %s interview', $db_qnaire->name ) ) ;
    
    $contents = array();

    $participant_list = $restric_site_id
                      ? db\participant::select_for_site( $db_site )
                      : db\participant::select();

    foreach( $participant_list as $db_participant )
    {
      $done = false;

      if( !is_null( $db_participant->status ) ) continue;      

      $consent_mod = new db\modifier();
      $consent_mod->where( 'event', '=', 'verbal accept' );
      $consent_mod->or_where( 'event', '=', 'written accept' );
      if( count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        $interview_mod = new db\modifier();
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $db_participant->get_interview_list( $interview_mod );
        $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
        if( $db_interview && $db_interview->completed )
        {
          foreach( $db_interview->get_qnaire()->get_phase_list() as $db_phase )
          {
            // figure out the token
            $token = db\limesurvey\record::get_token( $db_interview, $db_phase );

            // determine if the participant answered yes to the consent question
            $survey_mod = new db\modifier();
            if( $db_phase->repeated )
            {
              // replace the token's 0 with a database % wildcard
              $token = substr( $token, 0, -1 ).'%';
              $survey_mod->where( 'token', 'LIKE', $token );
            }
            else $survey_mod->where( 'token', '=', $token );

            db\limesurvey\survey::set_sid( $db_phase->sid );
            foreach( db\limesurvey\survey::select( $survey_mod ) as $db_survey )
            {
              if( $db_survey && 'Y' == $db_survey->get_response( $question_code ) )
              {
                $db_address = $db_participant->get_first_address();
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

    return parent::finish();
  }
}
?>
