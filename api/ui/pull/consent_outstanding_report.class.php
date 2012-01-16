<?php
/**
 * consent_outstanding_report.class.php
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
 * Consent outstanding report data.
 * 
 * @package sabretooth\ui
 */
class consent_outstanding_report extends base_report
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
    parent::__construct( 'consent_outstanding', $args );
  }

  public function finish()
  {
    // get report args
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    if( $restrict_site_id ) $db_site = new db\site( $restrict_site_id );

    $db_qnaire = new db\qnaire( $this->get_argument( 'restrict_qnaire_id' ) );
    $this->add_title( sprintf( 'Participants who have not remitted written consent for the '.
                               '%s interview', $db_qnaire->name ) ) ;

    $participant_list = $restrict_site_id
                      ? db\participant::select_for_site( $db_site )
                      : db\participant::select();

    $contents = array();
    // loop through participants searching for those who have completed their most recent interview
    foreach( $participant_list as $db_participant )
    {
      // dont bother with deceased or otherwise impaired
      if( !is_null( $db_participant->status ) ) continue;

      $consent_mod = new db\modifier();
      $consent_mod->where( 'event', '=', 'verbal accept' );
      if( count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        $interview_mod = new db\modifier();
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
        $db_participant->get_interview_list( $interview_mod );
        $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
        if( $db_interview && $db_interview->completed )
        {
          $db_address = $db_participant->get_primary_address();
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
        }
      } // end non-empty consent list search  
    } // end loop on participants 

    
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
