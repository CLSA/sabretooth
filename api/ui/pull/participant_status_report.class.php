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
   * Note: Running this report using the standard loop across all participants is too inefficient.
   * Instead, several custom queries are used by this report to get the required data.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $this->report->set_orientation( 'landscape' );

    $record_class_name = lib::get_class_name( 'database\record' );
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

    $setting_manager = lib::create( 'business\setting_manager' );
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

    $this->category_totals_list = array();
    if( 'Site' == $breakdown )
    {
      $site_mod = lib::create( 'database\modifier' );
      if( $is_supervisor )
        $site_mod->where( 'id', '=', $session->get_site()->id );
      foreach( $site_class_name::select( $site_mod ) as $db_site )
        $this->category_totals_list[ $db_site->name ] = $category_totals;

      // only include the "None" column if user isn't a supervisor
      if( !$is_supervisor ) $this->category_totals_list['None'] = $category_totals;

      $this->base_sql =
        'SELECT site.name AS category, temp_participant.id '.
        'FROM temp_participant '.
        'LEFT JOIN participant_site ON temp_participant.id = participant_site.participant_id '.
        'LEFT JOIN site ON participant_site.site_id = site.id ';
    }
    else if( 'Province' == $breakdown )
    {
      $region_mod = lib::create( 'database\modifier' );
      $region_mod->order( 'abbreviation' );
      $region_mod->where( 'country', '=', 'Canada' );
      if( $is_supervisor )
        $region_mod->where( 'site_id', '=', $session->get_site()->id );
      foreach( $region_class_name::select( $region_mod ) as $db_region )
        $this->category_totals_list[ $db_region->abbreviation ] = $category_totals;

      // only include the "None" column if user isn't a supervisor and province isn't restricted
      if( !$is_supervisor && !$restrict_province_id )
        $this->category_totals_list['None'] = $category_totals;

      $this->base_sql =
        'SELECT region.abbreviation AS category, temp_participant.id '.
        'FROM temp_participant '.
        'LEFT JOIN participant_primary_address '.
        'ON temp_participant.id = participant_primary_address.participant_id '.
        'LEFT JOIN address '.
        'ON participant_primary_address.address_id = address.id '.
        'LEFT JOIN region ON address.region_id = region.id '.
        'AND region.country = "Canada" ';
    }
    else // if( 'Quota' == $breakdown )
    {
      $age_group_class_name = lib::get_class_name( 'database\age_group' );
      $age_group_mod = lib::create( 'database\modifier' );
      $age_group_mod->order( 'lower' );
      foreach( $age_group_class_name::select( $age_group_mod ) as $db_age_group )
      {
        $this->category_totals_list[ 'M'.$db_age_group->lower ] = $category_totals;
        $this->category_totals_list[ 'F'.$db_age_group->lower ] = $category_totals;
      }

      $this->base_sql =
        'SELECT '.
        'CONCAT( IF( temp_participant.gender = "female", "F", "M" ), age_group.lower ) AS category, '.
        'temp_participant.id '.
        'FROM temp_participant '.
        'JOIN age_group ON temp_participant.age_group_id = age_group.id ';
    }

    // we will need a table containing the most recent 
    // to avoid double-counting participants we create a temporary table with all participants,
    // then remove them as they fall into a category
    $temp_table_sql = 
      'CREATE TEMPORARY TABLE temp_participant SELECT participant.* '.
      'FROM participant ';
    if( 'Province' == $breakdown || $restrict_province_id ) $temp_table_sql .=
      'LEFT JOIN participant_primary_address '.
      'ON participant.id = participant_primary_address.participant_id '.
      'LEFT JOIN address '.
      'ON participant_primary_address.address_id = address.id ';
    if( 'Province' == $breakdown ) $temp_table_sql .=
      'LEFT JOIN region ON address.region_id = region.id '.
      'AND region.country = "Canada" ';

    // add restrictions based on input parameters
    $modifier = lib::create( 'database\modifier' );
    if( $is_supervisor ) $modifier->where( 'site_id', '=', $session->get_site()->id );
    if( $restrict_province_id ) $modifier->where( 'address.region_id', '=', $restrict_province_id );
    if( 0 < $restrict_source_id ) $modifier->where( 'source_id', '=', $restrict_source_id );
    if( $restrict_start_date )
      $modifier->where(
        'participant.create_timestamp', '>=', $start_datetime_obj->format( 'Y-m-d' ) );
    if( $restrict_end_date )
      $modifier->where(
        'participant.create_timestamp', '<=', $end_datetime_obj->format( 'Y-m-d' ) );
    $record_class_name::db()->execute( sprintf( '%s %s', $temp_table_sql, $modifier->get_sql() ) );

    // total of all phone calls
    $sub_cat = 'Total number of calls';
    $extra_sql = sprintf( 'JOIN interview ON temp_participant.id = interview.participant_id '.
                          'AND interview.qnaire_id = %s '.
                          'JOIN assignment ON interview.id = assignment.interview_id '.
                          'JOIN phone_call ON assignment.id = phone_call.assignment_id ',
                          $db_qnaire->id );

    $modifier = lib::create( 'database\modifier' );
    $modifier->group( 'category' );
    $rows = $record_class_name::db()->get_all(
      sprintf( '%s %s %s',
               preg_replace( '/temp_participant\.id/', 'COUNT(*) AS total', $this->base_sql, 1 ),
               $extra_sql,
               $modifier->get_sql() ) );
    foreach( $rows as $row )
    {
      if( is_null( $row['category'] ) )
      {
        if( array_key_exists( 'None', $this->category_totals_list ) )
          $this->category_totals_list['None'][$sub_cat] = $row['total'];
      }
      else $this->category_totals_list[$row['category']][$sub_cat] = $row['total'];
    }

    // deceased
    $sub_cat = 'Deceased';
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'temp_participant.status', '=', 'deceased' );
    $this->set_category_totals( $sub_cat, '', $modifier );

    // final status not null
    $sub_cat = 'Permanent condition (excl. deceased)';
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'temp_participant.status', '!=', NULL );
    $this->set_category_totals( $sub_cat, '', $modifier );

    // unassigned past appointment
    $sub_cat = 'Appointment (missed)';
    $extra_sql = sprintf(
      'JOIN participant_last_appointment '.
      'ON temp_participant.id = participant_last_appointment.participant_id '.
      'JOIN appointment '.
      'ON participant_last_appointment.appointment_id = appointment.id '.
      'AND appointment.assignment_id IS NULL '.
      'AND UTC_TIMESTAMP() > appointment.datetime + INTERVAL %s MINUTE ',
      $setting_manager->get_setting( 'appointment', 'call post-window' ) );
    $this->set_category_totals( $sub_cat, $extra_sql );

    // unassigned future appointment (all remaining unassigned appointments
    $sub_cat = 'Appointment';
    $extra_sql =
      'JOIN participant_last_appointment '.
      'ON temp_participant.id = participant_last_appointment.participant_id '.
      'JOIN appointment '.
      'ON participant_last_appointment.appointment_id = appointment.id '.
      'AND appointment.assignment_id IS NULL ';
    $this->set_category_totals( $sub_cat, $extra_sql );

    // last consent retract
    $sub_cat = 'Retracted from study';
    $extra_sql =
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'JOIN consent '.
      'ON participant_last_consent.consent_id = consent.id '.
      'AND consent.event = "retract" ';
    $this->set_category_totals( $sub_cat, $extra_sql );

    // last consent withdraw
    $sub_cat = 'Withdrawn from study';
    $extra_sql =
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'JOIN consent '.
      'ON participant_last_consent.consent_id = consent.id '.
      'AND consent.event = "withdraw" ';
    $this->set_category_totals( $sub_cat, $extra_sql );

    // no interviews
    $sub_cat = 'Not yet called';
    $extra_sql = sprintf(
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.id', '=', NULL );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has a complete interview
    // last consent: none
    $sub_cat = 'Completed interview - No consent information';
    $extra_sql = sprintf(
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.completed', '=', true );
    $modifier->where( 'participant_last_consent.consent_id', '=', NULL );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has a complete interview
    // last consent: written accept
    $sub_cat = 'Completed interview - Consent received';
    $extra_sql = sprintf(
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'JOIN consent '.
      'ON participant_last_consent.consent_id = consent.id '.
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.completed', '=', true );
    $modifier->where( 'consent.event', '=', 'written accept' );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has a complete interview
    // last consent: verbal deny, verbal accept or written deny
    $sub_cat = 'Completed interview - Consent not received';
    $extra_sql = sprintf(
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'JOIN consent '.
      'ON participant_last_consent.consent_id = consent.id '.
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.completed', '=', true );
    $modifier->where( 'consent.event', 'IN',
      array( 'verbal deny', 'verbal accept', 'written deny' ) );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has an incomplete interview
    // last consent: verbal or written deny
    $sub_cat = 'Hard refusal';
    $extra_sql = sprintf(
      'JOIN participant_last_consent '.
      'ON temp_participant.id = participant_last_consent.participant_id '.
      'JOIN consent '.
      'ON participant_last_consent.consent_id = consent.id '.
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.completed', '=', false );
    $modifier->where( 'consent.event', 'IN', array( 'verbal deny', 'written deny' ) );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has an incomplete interview
    // failed call count >= max failed calls
    $sub_cat = 'Sourcing Required';
    // get the max failed calls setting and invoke the temporary table needed in the join
    $max_failed_calls =
      lib::create( 'business\setting_manager' )->get_setting( 'calling', 'max failed calls' );
    $interview_class_name::create_interview_failed_call_count();
    $extra_sql = sprintf(
      'LEFT JOIN interview '.
      'ON temp_participant.id = interview.participant_id '.
      'AND interview.qnaire_id = %s '.
      'JOIN interview_failed_call_count '.
      'ON interview.id = interview_failed_call_count.interview_id ',
      $db_qnaire->id );
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.completed', '=', false );
    $modifier->where( 'interview_failed_call_count.total', '>=', $max_failed_calls );
    $this->set_category_totals( $sub_cat, $extra_sql, $modifier );

    // has an incomplete interview
    // last phone call status
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $status )
    {
      $sub_cat = ucfirst( $status );
      $extra_sql = sprintf( 
        'LEFT JOIN interview '.
        'ON temp_participant.id = interview.participant_id '.
        'AND interview.qnaire_id = %s '.
        'JOIN interview_last_assignment '.
        'ON interview.id = interview_last_assignment.interview_id '.
        'JOIN assignment_last_phone_call '.
        'ON interview_last_assignment.assignment_id = assignment_last_phone_call.assignment_id '.
        'JOIN phone_call '.
        'ON assignment_last_phone_call.phone_call_id = phone_call.id ',
        $db_qnaire->id );
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'interview.completed', '=', false );
      $modifier->where( 'phone_call.status', '=', $status );
      $this->set_category_totals( $sub_cat, $extra_sql, $modifier );
    }

    $header = array( 'Current Outcome' );

    //calculate a grand total column if we have more than one totals column
    if( 1 < count( $this->category_totals_list ) )
      $this->category_totals_list['Grand Total'] = $category_totals;

    foreach( $this->category_totals_list as $category => $totals )
    {
      $header[] = $category;
      if( 'Grand Total' != $category )
      {
        $this->category_totals_list[$category]['Grand Total Attempted'] =
          array_sum( array_slice(
            $totals, $phone_call_status_start_index, $phone_call_status_count ) );

        $tci = array_sum( array_slice( $totals, 0, 4 ) );

        $this->category_totals_list[$category]['Total completed interviews'] = $tci;
        $denom = $tci + $totals['Hard refusal']
                      + $totals['Soft refusal']
                      + $totals['Withdrawn from study'];

        $this->category_totals_list[$category]['Response rate (incl. soft refusals)'] =
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';

        $denom = $tci + $totals['Withdrawn from study']
                      + $totals['Hard refusal'];

        $this->category_totals_list[$category]['Response rate (excl. soft refusals)'] =
          $denom ? sprintf( '%0.2f', $tci / $denom ) : 'NA';

        if( array_key_exists( 'Grand Total', $this->category_totals_list ) )
          foreach( array_keys( $totals ) as $column )
            $this->category_totals_list['Grand Total'][ $column ] +=
              $this->category_totals_list[$category][ $column ];

        $tc = $this->category_totals_list[$category]['Total number of calls'];
        $this->category_totals_list[$category]['Completed interviews / total number of calls'] =
          0 < $tc ? sprintf( '%0.2f', $tci / $tc ) : 'NA';
      }
    }

    if( array_key_exists( 'Grand Total', $this->category_totals_list ) )
    {
      $gtci = $this->category_totals_list['Grand Total']['Total completed interviews'];

      $denom =
            $gtci +
            $this->category_totals_list['Grand Total']['Hard refusal'] +
            $this->category_totals_list['Grand Total']['Soft refusal'];

      $this->category_totals_list['Grand Total']['Response rate (incl. soft refusals)'] =
        $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';

      $denom =
            $gtci +
            $this->category_totals_list['Grand Total']['Withdrawn from study'] +
            $this->category_totals_list['Grand Total']['Hard refusal'];

      $this->category_totals_list['Grand Total']['Response rate (excl. soft refusals)'] =
        $denom ? sprintf( '%0.2f', $gtci / $denom ) : 'NA';

      $gtc = $this->category_totals_list['Grand Total']['Total number of calls'];
      $this->category_totals_list['Grand Total']['Completed interviews / total number of calls'] =
        0 < $gtc ? sprintf( '%0.2f', $gtci / $gtc ) : 'NA';
    }

    // build the final 2D content array
    $temp_content = array( array_keys( $category_totals ) );
    foreach( $this->category_totals_list as $totals ) $temp_content[] = array_values( $totals );

    // transpose from column-wise to row-wise
    $content = array();
    foreach( $temp_content as $key => $subarr )
      foreach( $subarr as $subkey => $subvalue )
        $content[ $subkey ][ $key ] = $subvalue;

    $this->add_table( NULL, $header, $content, NULL, NULL, array( 'A' ) );
  }

  /**
   * Internal function for setting the category totals for this report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sub_category The name of the sub-category to set
   * @param string $extra_sql Extra sql to add to the base query
   * @param database\modifier $modifier The modifier to apply to the query
   * @access private
   */
  private function set_category_totals( $sub_category, $extra_sql = '', $modifier = NULL )
  {
    $record_class_name = lib::get_class_name( 'database\record' );

    // get the count for each category
    $rows = $record_class_name::db()->get_all(
      sprintf( '%s %s %s',
               $this->base_sql,
               $extra_sql,
               is_null( $modifier ) ? '' : $modifier->get_sql() ) );
    $id_list = array();
    foreach( $rows as $row )
    {
      if( is_null( $row['category'] ) )
      {
        if( array_key_exists( 'None', $this->category_totals_list ) )
          $this->category_totals_list['None'][$sub_category]++;
      }
      else $this->category_totals_list[$row['category']][$sub_category]++;
      $id_list[] = $row['id'];
    }
    
    if( count( $id_list ) )
    {
      $id_string_list = implode( ',', $id_list );
      $record_class_name::db()->execute( sprintf(
        'DELETE FROM temp_participant WHERE id IN ( %s )',
        $id_string_list ) );
    }
  }

  /**
   * Internal array used to count category totals
   * @var array
   * @access protected
   */
  private $category_totals_list = array();

  /**
   * Base query used to gather category totals
   * @var string
   * @access private
   */
  private $base_sql = '';
}
?>
