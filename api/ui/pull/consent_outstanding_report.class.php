<?php
/**
 * consent_outstanding_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Consent outstanding report data.
 */
class consent_outstanding_report extends \cenozo\ui\pull\base_report
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

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get report args
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    if( $restrict_site_id ) $db_site = lib::create( 'database\site', $restrict_site_id );

    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $this->add_title( sprintf( 'Participants who have not remitted written consent for the '.
                               '%s interview', $db_qnaire->name ) ) ;

    // modifiers common to each iteration of the following loops
    $consent_mod = lib::create( 'database\modifier' );
    $consent_mod->where( 'event', '=', 'verbal accept' );

    $contents = array();

    // loop through participants searching for those who have completed their most recent interview
    $participant_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $participant_mod->where( 'participant_site.site_id', '=', $restrict_site_id );
    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      // dont bother with deceased or otherwise impaired
      if( !is_null( $db_participant->status ) ) continue;

      if( count( $db_participant->get_consent_list( $consent_mod ) ) )
      {
        $interview_mod = lib::create( 'database\modifier' );
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
  }
}
?>
