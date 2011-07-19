<?php
/**
 * consent_form.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Consent form report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class consent_form extends base_report
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
    
    $this->add_title( 'Consent Form Required Report' );
    $this->add_title(
      'A list of participant\'s who have indicated they require a new consent form' );

    $now_datetime_obj = util::get_datetime_object();
    $generated = 'Generated on '.$now_datetime_obj->format( 'Y-m-d' ).
                 ' at '.$now_datetime_obj->format( 'H:i' );
    $this->add_title( $generated );
    
    $contents = array();

    // loop through every participant searching for those who have no written consent
    foreach( db\participant::select() as $db_participant )
    {
      $done = false;

      $consent_mod = new modifier();
      $consent_mod->where( 'event', '=', 'written accept' );
      $consent_mod->or_where( 'event', '=', 'written deny' );
      $consent_mod->or_where( 'event', '=', 'retract' );
      $consent_mod->or_where( 'event', '=', 'withdraw' );
      if( 0 == count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        // now go through their interviews until the consent question code is found
        foreach( $db_participant->get_interview_list() as $db_interview )
        {
          foreach( $db_interivew->get_qnaire()->get_phase_list() as $db_phase )
          {
            // figure out the token
            $token = db\limesurvey\record::get_token( $db_interview, $db_phase );

            // determine if the participant answered yes to the consent question
            $survey_mod = new modifier();
            if( $db_phase->repeated )
            {
              // replace the token's 0 with a database % wildcard
              $token = substr( $token, 0, -1 ).'%';
              $survey_mod->where( 'token', 'LIKE', $token );
            }
            else $survey_mod->where( 'token', '=', $token );

            db\limesurvey\survey::$table_sid = $db_phase->sid;
            foreach( db\limesurvey\survey::select( $survey_mod ) as $db_survey )
            {
              if( $db_survey && 'Y' == $db_survey->get_response( $question_code ) )
              {
                $db_address = $db_participant->get_first_address();
                $db_region = $db_address->get_region();
                $db_phone = NULL; // TODO

                $contents[] = array(
                  $db_partitipant->uid,
                  $db_interview->complete ? 'Yes' : 'No',
                  $db_participant->get_last_contacted_phone_call()->start_datetime,
                  $db_participant->first_name,
                  $db_participant->last_name,
                  $db_address->address1,
                  $db_address->address2,
                  $db_address->city,
                  $db_region->abbreviation,
                  $db_region->country,
                  $db_address->postcode,
                  $db_phone->number );

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

    $this->add_table( NULL, $header, $contents, $footer );
  }
}
?>
