<?php
/**
 * participant_status_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Participant status report data.
 * 
 * @abstract
 */
class participant_status_report extends \cenozo\ui\pull\base_report
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

  /**
   * Builds the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $site_class_name = lib::get_class_name( 'database\site' );

    $session = lib::create( 'business\session' );
    $is_supervisor = 'supervisor' == $session->get_role()->name;

    // get the report arguments
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $breakdown = $this->get_argument( 'breakdown' );
    $restrict_source_id = $this->get_argument( 'restrict_source_id' );

    $this->add_title( 
      sprintf( 'Listing of categorical totals pertaining to '.
               'the %s interview', $db_qnaire->name ) ) ;

    $breakdown = $this->get_argument( 'breakdown' );
    $restrict_province_id = $this->get_argument( 'restrict_province_id' );
    $restrict_start_date = $this->get_argument( 'restrict_start_date' );
    $restrict_end_date = $this->get_argument( 'restrict_end_date' );
    $now_datetime_obj = util::get_datetime_object();
    $start_datetime_obj = NULL;
    $end_datetime_obj = NULL;
    
    if( $restrict_province_id || $restrict_source_id )
    {
      $province_name = $restrict_province_id
                     ? lib::create( 'database\region', $restrict_province_id )->name
                     : false;
      $source_name = $restrict_source_id
                   ? lib::create( 'database\source', $restrict_source_id )->name
                   : false;

      if( $province_name && $source_name )
        $title = 'Restricted to '.$province_name.', '.$source_name;
      else if( $province_name )
        $title = 'Restricted to '.$province_name;
      else if( $source_name )
        $title = 'Restricted to '.$source_name;
                           
      $this->add_title( $title );
    }

    if( $restrict_start_date )
    {
      $start_datetime_obj = util::get_datetime_object( $restrict_start_date );
      if( $start_datetime_obj > $now_datetime_obj )
        $start_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_end_date )
    {
      $end_datetime_obj = util::get_datetime_object( $restrict_end_date );
      if( $end_datetime_obj > $now_datetime_obj )
        $end_datetime_obj = clone $now_datetime_obj;
    }
    if( $restrict_start_date && $restrict_end_date && $end_datetime_obj < $start_datetime_obj )
    {
      $temp_datetime_obj = clone $start_datetime_obj;
      $start_datetime_obj = clone $end_datetime_obj;
      $end_datetime_obj = clone $temp_datetime_obj;
    }

    $category_totals = array(
      'Completed interview - Consent not received' => 0,
      'Completed interview - Consent received' => 0,
      'Completed interview - No consent information' => 0,
      'Retracted from study' => 0,
      'Withdrawn from study' => 0,
      'Hard refusal' => 0,
      'Soft refusal' => 0,
      'Appointment' => 0,
      'Appointment (missed)' => 0,
      'Sourcing Required' => 0 );
      
    // add call results
    $phone_call_status_start_index = count( $category_totals ) - 1; // includes "sourcing required" above
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $status )
      $category_totals[ ucfirst( $status ) ] = 0;
    $phone_call_status_count = count( $category_totals ) - $phone_call_status_start_index;

    $category_totals = array_merge( $category_totals, array(
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
    $blank[] = count( $category_totals ) - 3;

    $category_totals_list = array();
    if( 'Site' == $breakdown )
    {
      $site_mod = lib::create( 'database\modifier' );
      if( $is_supervisor )
        $site_mod->where( 'id', '=', $session->get_site()->id );
      foreach( $site_class_name::select( $site_mod ) as $db_site )
        $category_totals_list[ $db_site->name ] = $category_totals; 

      // only include the "None" column if user isn't a supervisor
      if( !$is_supervisor ) $category_totals_list[ 'None' ] = $category_totals;
    }
    else if( 'Province' == $breakdown )
    {
      $region_mod = lib::create( 'database\modifier' );
      $region_mod->order( 'abbreviation' );
      $region_mod->where( 'country', '=', 'Canada' );
      if( $is_supervisor )
        $region_mod->where( 'site_id', '=', $session->get_site()->id );
      foreach( $region_class_name::select( $region_mod ) as $db_region )
        $category_totals_list[ $db_region->abbreviation ] = $category_totals; 

      // only include the "None" column if user isn't a supervisor and province isn't restricted
      if( !$is_supervisor && !$restrict_province_id )
        $category_totals_list[ 'None' ] = $category_totals;
    }
    else if( 'Quota' == $breakdown )
    {
      $age_group_class_name = lib::get_class_name( 'database\age_group' );
      $age_group_mod = lib::create( 'database\modifier' );
      $age_group_mod->order( 'lower' );
      foreach( $age_group_class_name::select( $age_group_mod ) as $db_age_group )
      {
        $category_totals_list[ 'M'.$db_age_group->lower ] = $category_totals;
        $category_totals_list[ 'F'.$db_age_group->lower ] = $category_totals;
      }
    }

    // create a temporary table to quickly select participant's last consent
    $participant_class_name::db()->execute(
      'CREATE TEMPORARY TABLE temp_participant_last_consent '.
      'SELECT participant.id AS participant_id, t1.id AS consent_id '.
      'FROM participant '.
      'LEFT JOIN consent AS t1 '.
      'ON participant.id = t1.participant_id '.
      'AND t1.date = ( '.
      '  SELECT MAX( t2.date ) '.
      '  FROM consent AS t2 '.
      '  WHERE t1.participant_id = t2.participant_id ) '.
      'GROUP BY participant.id' );

    $participant_mod = lib::create( 'database\modifier' );
    if( $is_supervisor ) $participant_mod->where( 'site_id', '=', $session->get_site()->id );
    if( $restrict_province_id ) $participant_mod->where( 'address.region_id', '=', $restrict_province_id );
    if( 0 < $restrict_source_id ) $participant_mod->where( 'source_id', '=', $restrict_source_id );
    if( $restrict_start_date )
      $participant_mod->where(
        'participant.create_timestamp', '>=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( $restrict_end_date )
      $participant_mod->where(
        'participant.create_timestamp', '<=', $end_datetime_obj->format( 'Y-m-d' ) );
    $participant_list = $participant_class_name::select( $participant_mod );
    foreach( $participant_list as $db_participant )
    {
      $db_site = $db_participant->get_primary_site();

      if( 'Site' == $breakdown )
      {
        $category = is_null( $db_site )
                ? 'None'
                : $db_participant->get_primary_site()->name;
      }
      else if( 'Province' == $breakdown )
      {
        $db_address = $db_participant->get_primary_address();
        $category = is_null( $db_address )
                ? 'None'
                : $db_address->get_region()->abbreviation;
      }
      else if( 'Quota' == $breakdown )
      {
        $category = sprintf( '%s%s',
          'female' == $db_participant->gender ? 'F' : 'M',
          $db_participant->get_age_group()->lower );
      }

      // get the maximum number of failed calls before sourcing is required
      $max_failed_calls = lib::create( 'business\setting_manager' )->get_setting(
        'calling', 'max failed calls', $db_site );

      // don't include the "None" column if a supervisor is running the report
      if( $is_supervisor && 'None' == $category ) continue;

      $phone_call_mod = lib::create( 'database\modifier' );
      $phone_call_mod->where( 'participant.id', '=', $db_participant->id );
      $category_totals_list[ $category ][ 'Total number of calls' ] +=
        $phone_call_class_name::count( $phone_call_mod );

      if( 'deceased' == $db_participant->status )
      {
        $category_totals_list[ $category ][ 'Deceased' ]++;
      }
      else if( !is_null( $db_participant->status ) )
      {
        $category_totals_list[ $category ][ 'Permanent condition (excl. deceased)' ]++;    
      }
      else
      {
        $now_datetime_obj = util::get_datetime_object();
        $appointment_mod = lib::create( 'database\modifier' );
        $appointment_mod->where( 'assignment_id', '=', NULL );
        $appointment_mod->where( 'datetime', '>', $now_datetime_obj->format( 'Y-m-d H:i:s' ) );
        $has_appointment = false;
        foreach( $db_participant->get_appointment_list( $appointment_mod ) as $db_appointment )
        {
          if( 'missed' == $db_appointment->get_state() )
          {
            $category_totals_list[ $category ][ 'Appointment (missed)' ]++;
            $has_appointment = true;
            break;
          }
          else
          {
            $category_totals_list[ $category ][ 'Appointment' ]++;
            $has_appointment = true;
            break;
          }
        }
        if( $has_appointment ) continue;

        $interview_mod = lib::create( 'database\modifier' );
        $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id ); 
        $interview_list = $db_participant->get_interview_list( $interview_mod );

        // first deal with withdrawn and retracted participants

        // For performance issues we cannot use the participant record's get_last_consent() method.
        // Instead, we use the temporary table created before this loop.
        $consent_id = $participant_class_name::db()->get_one(
          sprintf( 'SELECT consent_id '.
                   'FROM temp_participant_last_consent '.
                   'WHERE participant_id = %s',
                   $db_participant->id ) );
        $db_consent = is_null( $consent_id )
                    ? NULL
                    : lib::create( 'database\consent', $consent_id );
        if( !is_null( $db_consent ) && 'retract' == $db_consent->event )
        {
          $category_totals_list[ $category ][ 'Retracted from study' ]++;
        }
        else if( !is_null( $db_consent ) && 'withdraw' == $db_consent->event )
        {
          $category_totals_list[ $category ][ 'Withdrawn from study' ]++;
        }
        else if( 0 == count( $interview_list ) )
        {
          $category_totals_list[ $category ][ 'Not yet called' ]++;
        }
        else
        {
          $db_interview = current( $interview_list );
          if( $db_interview->completed )
          {
            if( is_null( $db_consent ) )
            {
              $category_totals_list[ $category ][ 'Completed interview - No consent information' ]++;
            }
            else if( 'written accept' == $db_consent->event )
            {
              $category_totals_list[ $category ][ 'Completed interview - Consent received' ]++;
            }
            else if( 'verbal deny'   == $db_consent->event ||
                     'verbal accept' == $db_consent->event ||
                     'written deny'  == $db_consent->event )
            {
              $category_totals_list[ $category ][ 'Completed interview - Consent not received' ]++;
            }
          }
          else if( !is_null( $db_consent ) &&
                   ( 'verbal deny'  == $db_consent->event ||
                     'written deny' == $db_consent->event ) )
          {
            $category_totals_list[ $category ][ 'Hard refusal' ]++;
          }
          else 
          {
            if( $max_failed_calls <= $db_interview->get_failed_call_count() )
            {
              $category_totals_list[ $category ][ 'Sourcing Required' ]++;
            }
            else
            {              
              $assignment_mod = lib::create( 'database\modifier' );
              $assignment_mod->order_desc( 'start_datetime' );
              $assignment_mod->where( 'end_datetime', '!=', NULL );
              $assignment_mod->limit( 1 );
              $assignment_list = $db_interview->get_assignment_list( $assignment_mod );
              if( 1 == count( $assignment_list ) )
              {
                $db_assignment = current( $assignment_list );

                // find the most recently completed phone call
                $phone_call_mod = lib::create( 'database\modifier' );
                $phone_call_mod->order_desc( 'start_datetime' );
                $phone_call_mod->where( 'end_datetime', '!=', NULL );
                $phone_call_mod->limit( 1 );
                $db_phone_call = current( $db_assignment->get_phone_call_list( $phone_call_mod ) );
                if( $db_phone_call )
                  $category_totals_list[ $category ][ ucfirst( $db_phone_call->status ) ]++;
              }
            }  
          }// end interview not completed
        }// end non empty interview list
      }// end if not deceased or some condition
    }// end participants
    
    $header = array( 'Current Outcome' );
   
    //calculate a grand total column if we have more than one totals column
    if( 1 < count( $category_totals_list ) )
      $category_totals_list[ 'Grand Total' ] = $category_totals;

    foreach( $category_totals_list as $category => $totals )
    {
      $header[] = $category;
      if( 'Grand Total' != $category )
      {
        $category_totals_list[ $category ][ 'Grand Total Attempted' ] = 
          array_sum( array_slice(
            $totals, $phone_call_status_start_index, $phone_call_status_count ) );

        $tci = array_sum( array_slice( $totals, 0, 4 ) );

        $category_totals_list[ $category ][ 'Total completed interviews' ] = $tci;
        $denom = $tci + $totals[ 'Hard refusal' ] 
                      + $totals[ 'Soft refusal' ] 
                      + $totals[ 'Withdrawn from study' ];

        $category_totals_list[ $category ][ 'Response rate (incl. soft refusals)' ] =  
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';
                  
        $denom = $tci + $totals[ 'Withdrawn from study' ] 
                      + $totals[ 'Hard refusal' ];

        $category_totals_list[ $category ][ 'Response rate (excl. soft refusals)' ] = 
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';

        if( array_key_exists( 'Grand Total', $category_totals_list ) )
          foreach( array_keys( $totals ) as $column )
            $category_totals_list[ 'Grand Total' ][ $column ] +=
              $category_totals_list[ $category ][ $column ];
        
        $tc = $category_totals_list[ $category ][ 'Total number of calls' ];
        $category_totals_list[ $category ][ 'Completed interviews / total number of calls' ] =
          0 < $tc ? sprintf( '%0.2f', $tci / $tc ) : 'NA';
      }
    }

    if( array_key_exists( 'Grand Total', $category_totals_list ) )
    {
      $gtci = $category_totals_list[ 'Grand Total' ][ 'Total completed interviews' ];

      $denom =
            $gtci + 
            $category_totals_list[ 'Grand Total' ][ 'Hard refusal' ] + 
            $category_totals_list[ 'Grand Total' ][ 'Soft refusal' ];

      $category_totals_list[ 'Grand Total' ][ 'Response rate (incl. soft refusals)' ] = 
        $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';

      $denom = 
            $gtci + 
            $category_totals_list[ 'Grand Total' ][ 'Withdrawn from study' ] + 
            $category_totals_list[ 'Grand Total' ][ 'Hard refusal' ];

      $category_totals_list[ 'Grand Total' ][ 'Response rate (excl. soft refusals)' ] = 
        $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';
      
      $gtc = $category_totals_list[ 'Grand Total' ][ 'Total number of calls' ];
      $category_totals_list[ 'Grand Total' ][ 'Completed interviews / total number of calls' ] =
        0 < $gtc ? sprintf( '%0.2f', $gtci / $gtc ) : 'NA';
    }

    // build the final 2D content array
    $temp_content = array( array_keys( $category_totals ) );
    foreach( $category_totals_list as $totals ) $temp_content[] = array_values( $totals );

    // transpose from column-wise to row-wise
    $content = array();
    foreach( $temp_content as $key => $subarr )
      foreach( $subarr as $subkey => $subvalue )
        $content[ $subkey ][ $key ] = $subvalue;
   
    $this->add_table( NULL, $header, $content, NULL, $blank );
  }
}
?>
