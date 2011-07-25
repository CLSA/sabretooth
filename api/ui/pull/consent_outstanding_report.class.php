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
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0);

    $title = 'Written Consent Outstading Report';
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_site->name;
    }

    $this->add_title( $title );

    $contents = array();

    // loop through participants searching for those who have completed their most recent interview
    foreach( db\participant::select() as $db_participant )
    {
      // dont bother with deceased or otherwise impaired
      if( !is_null( $db_participant->status ) ) continue;

      
      // only grab the first interview for now
      $interview_list = $db_participant->get_interview_list();
      
      if( 0 == count( $interview_list ) ) continue;

      $done = false;

      $db_interview = current( $interview_list );
      if( $db_interview->completed )
      {
        $db_consent = $db_participant->get_last_consent();

       /// get_consent_list
       // loop through them checking for 

        // if they finished the interview, can they have 
        // a denied or null consent?
        // should this be just non null consent with
        // verbal accept only? otherwise why request written consent if they denied it?
        if( !is_null( $db_consent ) && !(
            'written deny'   == $db_consent->event ||
            'written accept' == $db_consent->event ||
            'retract' == $db_consent->event ||
            'withdraw' == $db_consent->event ) )
        {
          $done = true;
        }          
      }
      
      if( $done )
      {
        // should this be primary or first address?
        $db_address = $db_participant->get_primary_address();
        $db_region = $db_address->get_region();
        // is this how we get DATE OF COMPLETION?
        // the returned phone call is either null or a new phone_call object
        $db_last_phone_call = $db_participant->get_last_contacted_phone_call();
        
        $date_completed = 'NA';
        if( $db_last_phone_call )
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
