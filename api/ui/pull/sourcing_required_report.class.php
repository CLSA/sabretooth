<?php
/**
 * sourcing_required_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Sourcing required report data.
 */
class sourcing_required_report extends \cenozo\ui\pull\base_report
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
    parent::__construct( 'sourcing_required', $args );
  }

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    throw lib::create(
      'exception\notice', 'This report has been temporarily disabled.', __METHOD__ );

    /*
    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get the report args
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );

    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $this->add_title( sprintf( 'Participants requiring sourcing for the '.
                               '%s interview', $db_qnaire->name ) ) ;

    // modifiers common to each iteration of the following loops
    $assignment_mod = lib::create( 'database\modifier' );
    $assignment_mod->order_desc( 'start_datetime' );
    $phone_call_mod = lib::create( 'database\modifier' );
    $phone_call_mod->order_desc( 'start_datetime' );
    $phone_call_mod->where( 'end_datetime', '!=', NULL );
    $phone_call_mod->limit( 1 );
        
    $contents = array();

    // loop through participants searching for those who have completed their most recent interview
    $participant_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $participant_mod->where( 'participant_site.site_id', '=', $restrict_site_id );
    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      // dont bother with deceased or otherwise impaired
      if( !is_null( $db_participant->status ) ) continue;

      $interview_mod = lib::create( 'database\modifier' );
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id );
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
      if( $db_interview && !$db_interview->completed )
      {
        // get the maximum number of failed calls before sourcing is required
        $max_failed_calls = lib::create( 'business\setting_manager' )->get_setting(
          'calling', 'max failed calls', $db_participant->get_primary_site() );

        $failed_calls = 0;
        $db_recent_failed_call = NULL;
        foreach( $db_interview->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          // find the most recently completed phone call
          $db_phone_call = current( $db_assignment->get_phone_call_list( $phone_call_mod ) );
          if( false != $db_phone_call && 'contacted' != $db_phone_call->status )
          {
            $failed_calls++;
            // since the calls are sorted most recent to first, this captures the most
            // recent failed call
            if( 1 == $failed_calls )
            {
              $db_recent_failed_call = $db_phone_call;
            }
          }
        }

        $done = false;

        if( $max_failed_calls <= $failed_calls )
        {
          $done = true;
        }
        else if( !is_null( $db_recent_failed_call ) )
        {              
          if( 'disconnected' == $db_recent_failed_call->status ||
              'wrong number' == $db_recent_failed_call->status )
          {
            $done = true;
          }
        }

        if( $done )
        {
          $db_address = $db_participant->get_primary_address();
          $db_region = $db_address->get_region();
        
          $date_completed = 'NA';
          if( !is_null( $db_recent_failed_call ) )
          {
            $date_completed = substr( $db_recent_failed_call->start_datetime, 0, 
              strpos( $db_recent_failed_call->start_datetime, ' ' ) );
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
      } // end non-null incomplete interview
    } // end loop on participants

    // TODO we need two alternate contacts added to the report fields
    // but this functionality will have to come from mastodon manager
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
    */
  }
}
?>
