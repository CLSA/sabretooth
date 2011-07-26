<?php
/**
 * sourcing_required_report.class.php
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
 * Sourcing required report data.
 * 
 * @package sabretooth\ui
 */
class sourcing_required_report extends base_report
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

  public function finish()
  {
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0);

    $title = 'Sourcing Required Report';
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_site->name;
    }

    $this->add_title( $title );

    $contents = array();

    $participant_list = $restric_site_id
                      ? db\participant::select_for_site( $db_site )
                      : db\participant::select();

    // loop through participants searching for those who have completed their most recent interview
    foreach( $participant_list as $db_participant )
    {
      // dont bother with deceased or otherwise impaired
      if( !is_null( $db_participant->status ) ) continue;

      $interview_list = $db_participant->get_interview_list();

      if( 0 == count( $interview_list ) ) continue;

      $db_interview = current( $interview_list );

      if( !$db_interview->completed )
      {
        $assignment_mod = new db\modifier();
        $assignment_mod->order_desc( 'start_datetime' );
        $failed_calls = 0;
        $db_recent_failed_call = NULL;
        foreach( $db_interview->get_assignment_list( $assignment_mod ) as $db_assignment )
        {
          // find the most recently completed phone call
          $phone_call_mod = new db\modifier();
          $phone_call_mod->order_desc( 'start_datetime' );
          $phone_call_mod->where( 'end_datetime', '!=', NULL );
          $phone_call_mod->limit( 1 );
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
        if( 10 <= $failed_calls )
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
