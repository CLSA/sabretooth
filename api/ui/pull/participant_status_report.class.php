<?php
/**
 * participant_status_report.class.php
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
 * Participant status report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class participant_status_report extends base_report
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
    parent::__construct( 'participant_status', $args );
  }

  public function finish()
  {
    // get the report arguments
    $db_qnaire = new db\qnaire( $this->get_argument( 'restrict_qnaire_id' ) );
    $restrict_by_site = 
      $this->get_argument( 'restrict_site_or_province_id' ) == 'Site' ? true : false;
    $this->add_title( 
      sprintf( 'Listing of categorical totals pertaining to '.
               'the %s interview', $db_qnaire->name ) ) ;

    $totals = array(
      'Completed interview - Consent not received' => 0,
      'Completed interview - Consent received' => 0,
      'Completed interview - Consent retracted' => 0,
      'Completed interview - No consent information' => 0,
      'Withdrawn from study' => 0,
      'Hard refusal' => 0,
      'Soft refusal' => 0,
      'Appointment' => 0,
      'Appointment (missed)' => 0,
      '10+ Unproductive Call Attempts' => 0 );
      
    // add call results
    $phone_call_status_start_index = count( $totals ) - 1; // includes 10+ above
    foreach( db\phone_call::get_enum_values( 'status' ) as $status )
      $totals[ ucfirst( $status ) ] = 0;
    $phone_call_status_count = count( $totals ) - $phone_call_status_start_index;

    $totals = array_merge( $totals, array(
      'Not yet called' => 0,
      'Deceased' => 0,
      'Permanent condition (excl. deceased)' => 0,
      'Grand Total Attempted' => 0,
      'Total completed interviews' => 0,
      'Response rate (incl. soft refusals)' => 0,
      'Response rate (excl. soft refusals)' => 0,
      'Total number of calls' => 0,
      'Completed interviews / total number of calls' => 0 ) );

    // insert a blank line before Total number of calls
    $blank = array();
    $blank[] = count( $totals) - 3;

    $grand_totals = array();
    if( $restrict_by_site )
    {
      foreach( db\site::select() as $db_site )
        $grand_totals[ $db_site->name ] = $totals; 
    }
    else
    {
      $region_mod = new db\modifier();
      $region_mod->order( 'abbreviation' );
      $region_mod->where( 'country', '=', 'Canada' );
      foreach( db\region::select($region_mod) as $db_region )
        $grand_totals[ $db_region->abbreviation ] = $totals; 
    }  

    // the last column of the report sums totals row-wise
    $grand_totals[ 'Grand Total' ] = $totals;
    
    foreach( db\participant::select() as $db_participant )
    {
      if( $restrict_by_site )
      {
        $locale = $db_participant->get_primary_site()->name;
      }
      else
      {
        $locale = $db_participant->get_primary_address()->get_region()->abbreviation;
      }

      $grand_totals[ $locale ][ 'Total number of calls' ] +=
        db\phone_call::count_for_participant( $db_participant );

      if( 'deceased' == $db_participant->status )
      {
        $grand_totals[ $locale ][ 'Deceased' ]++;
      }
      else if( !is_null( $db_participant->status ) )
      {
        $grand_totals[ $locale ][ 'Permanent condition (excl. deceased)' ]++;    
      }
      else
      {
        $now_datetime_obj = util::get_datetime_object();
        $appointment_mod = new db\modifier();
        $appointment_mod->where( 'assignment_id', '=', NULL );
        $appointment_mod->where( 'datetime', '>', $now_datetime_obj->format( 'Y-m-d H:i:s' ) );
        $has_appointment = false;
        foreach( $db_participant->get_appointment_list( $appointment_mod ) as $db_appointment )
        {
          if( 'missed' == $db_appointment->get_state() )
          {
            $grand_totals[ $locale ][ 'Appointment (missed)' ]++;
            $has_appointment = true;
            break;
          }
          else
          {
            $grand_totals[ $locale ][ 'Appointment' ]++;
            $has_appointment = true;
            break;
          }
        }
        if( $has_appointment ) continue;

        $interview_mod = new db\modifier();
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id ); 
        $interview_list = $db_participant->get_interview_list( $interview_mod );
        if( 0 == count( $interview_list ) )
        {
          $grand_totals[ $locale ][ 'Not yet called' ]++;
        }
        else
        {
          $db_interview = current( $interview_list );
          $db_consent = $db_participant->get_last_consent();
          if( $db_interview->completed )
          {
            if( is_null( $db_consent ) )
            {
              $grand_totals[ $locale ][ 'Completed interview - No consent information' ]++;
            }
            else if( 'written accept' == $db_consent->event )
            {
              $grand_totals[ $locale ][ 'Completed interview - Consent received' ]++;
            }
            else if( 'verbal deny'   == $db_consent->event ||
                     'verbal accept' == $db_consent->event ||
                     'written deny'  == $db_consent->event )
            {
              $grand_totals[ $locale ][ 'Completed interview - Consent not received' ]++;
            }
            else if( 'retract' == $db_consent->event )
            {
              $grand_totals[ $locale ][ 'Completed interview - Consent retracted' ]++;
            }
            else if( 'withdraw' == $db_consent->event )
            {
              $grand_totals[ $locale ][ 'Withdrawn from study' ]++;
            }
            else
            {
              log::err( sprintf( 'Unknown consent type "%s" found.', $db_consent->event ) );
            }
          }
          else if( 'verbal deny'  == $db_consent->event ||
                   'written deny' == $db_consent->event ||
                   'retract'      == $db_consent->event ||
                   'withdraw'     == $db_consent->event )
          {
            $grand_totals[ $locale ][ 'Hard refusal' ]++;
          }
          else 
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
              if( false != $db_phone_call )
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
            
            if( 10 <= $failed_calls )
            {
              $grand_totals[ $locale ][ '10+ Unproductive Call Attempts' ]++;
            }
            else if( !is_null( $db_recent_failed_call ) )
            {              
              $grand_totals[ $locale ][ ucfirst( $db_recent_failed_call->status ) ]++;
            }  
          }// end interview not completed
        }// end non empty interview list
      }// end if not deceased or some condition
    }// end participants
    
    $totals_keys = array_keys( $totals );
    $header = array( 'Current Outcome' );
   
    foreach( $grand_totals as $locale => $value )
    {
      $header[] = $locale;
      if( 'Grand Total' != $locale )
      {
        $grand_totals[ $locale ][ 'Grand Total Attempted' ] = 
          array_sum( array_slice(
            $value, $phone_call_status_start_index, $phone_call_status_count ) );

        $tci = array_sum( array_slice( $value, 0, 4 ) );

        $grand_totals[ $locale ][ 'Total completed interviews' ] = $tci;
        $denom = $tci + $value[ 'Hard refusal' ] 
                      + $value[ 'Soft refusal' ] 
                      + $value[ 'Withdrawn from study' ];

        $grand_totals[ $locale ][ 'Response rate (incl. soft refusals)' ] =  
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';
                  
        $denom = $tci + $value[ 'Withdrawn from study' ] 
                      + $value[ 'Hard refusal' ];

        $grand_totals[ $locale ][ 'Response rate (excl. soft refusals)' ] = 
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';

        foreach( $totals_keys as $column )
          $grand_totals[ 'Grand Total' ][ $column ] += $grand_totals[ $locale ][ $column ];
        
        $tc = $grand_totals[ $locale ][ 'Total number of calls' ];
        $grand_totals[ $locale ][ 'Completed interviews / total number of calls' ] =
          0 < $tc ? sprintf( '%0.2f', $tci / $tc ) : 'NA';
      }
    }

    $gtci = $grand_totals[ 'Grand Total' ][ 'Total completed interviews' ];

    $denom =
          $gtci + 
          $grand_totals[ 'Grand Total' ][ 'Hard refusal' ] + 
          $grand_totals[ 'Grand Total' ][ 'Soft refusal' ];

    $grand_totals[ 'Grand Total' ][ 'Response rate (incl. soft refusals)' ] = 
      $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';

    $denom = 
          $gtci + 
          $grand_totals[ 'Grand Total' ][ 'Withdrawn from study' ] + 
          $grand_totals[ 'Grand Total' ][ 'Hard refusal' ];

    $grand_totals[ 'Grand Total' ][ 'Response rate (excl. soft refusals)' ] = 
      $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';
    
    $gtc = $grand_totals[ 'Grand Total' ][ 'Total number of calls' ];
    $grand_totals[ 'Grand Total' ][ 'Completed interviews / total number of calls' ] =
      0 < $gtc ? sprintf( '%0.2f', $gtci / $gtc ) : 'NA';

    // build the final 2D content array
    $temp_content = array( $totals_keys );
    foreach( $grand_totals as $key => $column )
    {
      $temp_array = array();
      foreach( $column as $value )
      {
        $temp_array[] = $value;
      }
      $temp_content[] = $temp_array;
    }

    // transpose from column-wise to row-wise
    $content = array();
    foreach( $temp_content as $key => $subarr )
    {
      foreach( $subarr as $subkey => $subvalue )
      {
        $content[ $subkey ][ $key ] = $subvalue;
      }
    }
   
    $this->add_table( NULL, $header, $content, NULL, $blank );

    return parent::finish();
  }// end constructor
}// end class def
?>
