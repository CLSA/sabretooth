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
    $state_class_name = lib::get_class_name( 'database\state' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $site_class_name = lib::get_class_name( 'database\site' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );
    $db = $session->get_database();
    $is_supervisor = 'supervisor' == $session->get_role()->name;
    $db_application = $session->get_application();
    $db_site = $session->get_site();

    // get the report arguments
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $breakdown = $this->get_argument( 'breakdown' );
    $restrict_source_id = $this->get_argument( 'restrict_source_id' );

    $restrict_collection_id = $this->get_argument( 'restrict_collection_id', 0 );
    $db_collection = $restrict_collection_id
                   ? lib::create( 'database\collection', $restrict_collection_id )
                   : NULL;
    $restrict_cohort_id = $this->get_argument( 'restrict_cohort_id', 0 );
    $db_cohort = $restrict_cohort_id
               ? lib::create( 'database\cohort', $restrict_cohort_id )
               : NULL;
    $restrict_province_id = $this->get_argument( 'restrict_province_id' );

    $this->add_title(
      sprintf( 'Listing of categorical totals pertaining to '.
               'the %s interview', $db_qnaire->name ) ) ;

    if( !is_null( $db_collection ) )
      $this->add_title( 'restricted to the "'.$db_collection->name.'" collection' );
    if( !is_null( $db_collection ) )
      $this->add_title( 'restricted to the "'.$db_cohort->name.'" cohort' );

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

    $category_totals = array(
      'Completed interview' => 0,
      'Completed interview (negative consent)' => 0,
      'Incomplete interview (negative consent)' => 0,
      'Appointment' => 0,
      'Appointment (missed)' => 0 );

    // add call results
    $phone_call_status_start_index = count( $category_totals ) - 1;
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $status )
      $category_totals[ ucfirst( $status ) ] = 0;
    $phone_call_status_count = count( $category_totals ) - $phone_call_status_start_index;

    $category_totals['Not yet called'] = 0;
    $category_totals['Call in progress'] = 0;

    // add states
    $state_mod = lib::create( 'database\modifier' );
    $state_mod->order( 'rank' );
    foreach( $state_class_name::select( $state_mod ) as $db_state )
      $category_totals[ ucfirst( $db_state->name ) ] = 0;

    // add total number of calls
    $category_totals['Response rate'] = '0';
    $category_totals['Total number of calls'] = 0;

    $this->category_totals_list = array();
    if( 'Site' == $breakdown )
    {
      $site_mod = lib::create( 'database\modifier' );
      if( $is_supervisor )
        $site_mod->where( 'id', '=', $db_site->id );
      foreach( $site_class_name::select( $site_mod ) as $db_temp_site )
        $this->category_totals_list[ $db_temp_site->name ] = $category_totals;

      // only include the "None" column if user isn't a supervisor
      if( !$is_supervisor ) $this->category_totals_list['None'] = $category_totals;
    }
    else if( 'Province' == $breakdown )
    {
      // create a list of all regions which have a site assigned to them
      // (no matter what the language)
      $region_mod = lib::create( 'database\modifier' );
      $region_mod->group( 'region_site.region_id' );
      $region_mod->order( 'region.country' );
      $region_mod->order( 'region.abbreviation' );
      $region_mod->where( 'region_site.application_id', '=', $db_application->id );
      if( $is_supervisor )
        $region_mod->where( 'region_site.site_id', '=', $db_site->id );
      foreach( $region_class_name::select( $region_mod ) as $db_region )
        $this->category_totals_list[ $db_region->abbreviation ] = $category_totals;

      // only include the "None" column if user isn't a supervisor and province isn't restricted
      if( !$is_supervisor && !$restrict_province_id )
        $this->category_totals_list['None'] = $category_totals;
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
    }

    // we will need a table containing the most recent 
    // to avoid double-counting participants we create a temporary table with all participants,
    // then remove them as they fall into a category
    $select_sql = 'SELECT participant.*, participant_last_consent.accept, ';

    $modifier = lib::create( 'database\modifier' );
    $modfier->join( 'participant_last_consent',
      'participant.id', 'participant_last_consent.participant_id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'application_has_participant.participant_id', false );
    $join_mod->where( 'application_has_participant.application_id', '=', $db_application->id );
    $join_mod->where( 'application_has_participant.datetime', '!=', NULL );
    $modifier->join_modifier( 'application_has_participant', $join_mod );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
    $join_mod->where( 'application_has_participant.application_id', '=', 'participant_site.application_id', false );
    $modifier->left_join_modifier( 'participant_site', $join_mod );
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );

    if( 'Province' == $breakdown || $restrict_province_id )
    {
      $modifier->left_join( 'participant_primary_address',
        'participant.id', 'participant_primary_address.participant_id' );
      $modifier->left_join( 'address',
        'participant_primary_address.address_id', 'address.id' );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'address.region_id', '=', 'region.id', false );
      $join_mod->where( 'region.country', '=', 'Canada' );
      $modifier->left_join_modifier( 'region', $join_mod );
    }

    // define the category based on the breakdown type requested
    if( 'Site' == $breakdown )
    {
      $select_sql .= 'site.name AS category ';
    }
    else if( 'Province' == $breakdown )
    {
      $select_sql .= 'region.abbreviation AS category ';
    }
    else // 'Quota' == breakdown
    {
      $select_sql .=
        'IF( age_group.id IS NULL OR participant.gender IS NULL, '.
            'NULL, '.
            'CONCAT( IF( participant.gender = "female", "F", "M" ), age_group.lower ) '.
        ') AS category ';
      $modifier->left_join( 'age_group', 'participant.age_group_id', 'age_group.id' );
    }

    if( $is_supervisor )
    {
      $modifier->where( 'participant_site.site_id', '=', $db_site->id );
    }

    if( !is_null( $db_collection ) )
    {
      $modifier->join( 'collection_has_participant',
        'collection_has_participant.participant_id', 'participant.id' );
      $modifier->where( 'collection_has_participant.collection_id', '=', $db_collection->id );
    }
    if( !is_null( $db_cohort ) )
      $modifier->where( 'participant.cohort_id', '=', $db_cohort->id );
    if( $restrict_province_id )
      $modifier->where( 'participant.region_id', '=', $restrict_province_id );
    if( $restrict_province_id )
      $modifier->where( 'address.region_id', '=', $restrict_province_id );
    if( 0 < $restrict_source_id )
      $modifier->where( 'source_id', '=', $restrict_source_id );
    $record_class_name::db()->execute(
      sprintf( 'CREATE TEMPORARY TABLE temp_participant %s FROM participant %s',
               $select_sql,
               $modifier->get_sql() ) );
    $record_class_name::db()->execute(
      'ALTER TABLE temp_participant ADD INDEX dk_id ( id ), '.
                                   'ADD INDEX dk_category ( category ), '.
                                   'ADD INDEX dk_gender ( gender ), '.
                                   'ADD INDEX dk_age_group_id ( age_group_id ), '.
                                   'ADD INDEX dk_state_id ( state_id )' );

    // total of all phone calls
    $sub_cat = 'Total number of calls';
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'temp_participant.id', '=', 'interview.participant_id', false );
    $join_mod->where( 'interview.qnaire_id', '=', $db_qnaire->id );
    $temp_mod = lib::create( 'database\modifier' );
    $temp_mod->join_modifier( 'interview', $join_mod );
    $temp_mod->join( 'assignment', 'interview.id', 'assignment.interview_id' );
    $temp_mod->join( 'phone_call', 'assignment.id', 'phone_call.assignment_id' );
    $temp_mod->group( 'temp_participant.category' );
    $rows = $record_class_name::db()->get_all(
      sprintf( 'SELECT temp_participant.category, COUNT(*) AS total '.
               'FROM temp_participant %s',
               $temp_mod->get_sql() ) );

    foreach( $rows as $row )
    {
      if( is_null( $row['category'] ) )
      {
        if( array_key_exists( 'None', $this->category_totals_list ) )
          $this->category_totals_list['None'][$sub_cat] = $row['total'];
      }
      else $this->category_totals_list[$row['category']][$sub_cat] = $row['total'];
    }

    $base_mod = lib::create( 'database\modifier' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'temp_participant.id', '=', 'interview.participant_id', false );
    $join_mod->where( 'interview.qnaire_id', '=', $db_qnaire->id );
    $base_mod->join_modifier( 'interview', $join_mod );

    // has a complete interview (negative consent)
    $sub_cat = 'Completed interview (negative consent)';
    $modifier = clone $base_mod;
    $modifier->where( 'interview.completed', '=', true );
    $modifier->where( 'accept', '=', false );
    $this->set_category_totals( $sub_cat, $modifier );

    // has a complete interview
    $sub_cat = 'Completed interview';
    $modifier = clone $base_mod;
    $modifier->join_modifier( 'interview', $join_mod );
    $modifier->where( 'interview.completed', '=', true );
    $this->set_category_totals( $sub_cat, $modifier );

    // has an incomplete interview (negative consent)
    $sub_cat = 'Incomplete interview (negative consent)';
    $modifier = clone $base_mod;
    $modifier->where( 'interview.completed', '=', false );
    $modifier->where( 'accept', '=', false );
    $this->set_category_totals( $sub_cat, $modifier );

    // response rate
    foreach( $this->category_totals_list as $category => $totals )
    {
      $num = $totals['Completed interview'];
      $denom = $totals['Completed interview'] +
               $totals['Completed interview (negative consent)'] +
               $totals['Incomplete interview (negative consent)'];
      $this->category_totals_list[$category]['Response rate'] = 0 == $denom
                                                              ? 'n/a'
                                                              : $num / $denom;
    }

    // final state not null
    $state_mod = lib::create( 'database\modifier' );
    $state_mod->order( 'rank' );
    foreach( $state_class_name::select( $state_mod ) as $db_state )
    {
      $sub_cat = ucfirst( $db_state->name );
      $modifier = lib::create( 'database\modifier' );
      $modifier->where( 'temp_participant.state_id', '=', $db_state->id );
      $this->set_category_totals( $sub_cat, $modifier );
    }

    // currently assigned
    $sub_cat = 'Call in progress';
    $modifier = clone $base_mod;
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'interview.id','=','assignment.interview_id', false );
    $join_mod->where( 'assignment.end_datetime', '=', NULL );
    $modifier->join_modifier( 'assignment', $modifier );
    $modifier->where( 'interview.id', '=', NULL );
    $this->set_category_totals( $sub_cat, $modifier );

    // create the base appointment modifier
    $appointment_base_mod = clone $base_mod;
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'interview.interview_method_id','=','interview_method.id', false );
    $join_mod->where( 'interview_method.name', '=', 'operator' );
    $appointment_base_mod->join_modifier( 'interview_method', $join_mod );
    $appointment_base_mod->join( 'interview_last_appointment',
      'interview.id', 'interview_last_appointment.interview_id' );

    // unassigned past appointment
    $sub_cat = 'Appointment (missed)';
    $modifier = clone $appointment_base_mod;
    $join_mod->where(
      'interview_last_appointment.appointment_id', '=', 'appointment.id', false );
    $join_mod->where( 'interview_last_appointment.appointment_id', '=', 'appointment.id' );
    $join_mod->where( 'appointment.assignment_id', '=', NULL );
    $datetime = sprintf( 'appointment.datetime + INTERVAL %s MINUTE',
                         $setting_manager->get_setting( 'appointment', 'call post-window' ) );
    $join_mod->where( 'UTC_TIMESTAMP()', '>', $datetime, false );
    $modifier->join_modifier( 'appointment', $join_mod );

    $this->set_category_totals( $sub_cat, $modifier );

    // unassigned future appointment (all remaining unassigned appointments
    $sub_cat = 'Appointment';
    $modifier = clone $appointment_base_mod;
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where(
      'interview_last_appointment.appointment_id', '=', 'appointment.id', false );
    $join_mod->where( 'interview_last_appointment.appointment_id', '=', 'appointment.id' );
    $join_mod->where( 'appointment.assignment_id', '=', NULL );
    $modifier->join_modifier( 'appointment', $join_mod );
    $this->set_category_totals( $sub_cat, $modifier );

    // no interviews
    $sub_cat = 'Not yet called';
    $modifier = clone $base_mod;
    $modifier->where( 'interview.id', '=', NULL );
    $this->set_category_totals( $sub_cat, $modifier );

    // has an incomplete interview
    // failed call count >= max failed calls
    $sub_cat = 'Sourcing required';
    $modifier = clone $base_mod;
    // get the max failed calls setting and invoke the temporary table needed in the join
    $max_failed_calls =
      lib::create( 'business\setting_manager' )->get_setting( 'calling', 'max failed calls' );
    $interview_class_name::create_interview_failed_call_count();
    $modifier->join( 'interview_failed_call_count',
      'interview.id', '=', 'interview_failed_call_count.interview_id' );
    $modifier->where( 'interview.completed', '=', false );
    $modifier->where( 'interview_failed_call_count.total', '>=', $max_failed_calls );
    $this->set_category_totals( $sub_cat, $modifier );

    // has an incomplete interview
    // last phone call status
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $status )
    {
      $sub_cat = ucfirst( $status );
      $modifier = clone $base_mod;
      $modifier->join( 'interview_last_assignment',
        'interview.id', 'interview_last_assignment.interview_id ' );
      $modifier->join( 'assignment_last_phone_call',
        'interview_last_assignment.assignment_id', 'assignment_last_phone_call.assignment_id' );
      $modifier->join( 'phone_call', 'assignment_last_phone_call.phone_call_id', 'phone_call.id' );
      $modifier->where( 'interview.completed', '=', false );
      $modifier->where( 'phone_call.status', '=', $status );
      $this->set_category_totals( $sub_cat, $modifier );
    }

    // build the header and footer for all tables
    $header = array( '' );
    $header = array_merge( $header, array_keys( $this->category_totals_list ) );
    $header[] = 'Total';
    $footer = array_fill( 0, count( $header ), 'SUM()' );
    $footer[0] = '';

    // create the first table
    $content = array();
    $category_list = array(
      'Completed interview',
      'Completed interview (negative consent)',
      'Incomplete interview (negative consent)',
      'Appointment',
      'Appointment (missed)' );
    foreach( $category_list as $category )
    {
      $row = array( $category );
      foreach( $this->category_totals_list as $site => $totals )
        $row[] = $totals[$category];
      $row[] = array_sum( $row );
      $content[] = $row;     
    }
    
    $this->add_table(
      'Completed Interviews and Appointments', $header, $content, $footer, NULL, array( 'A' ) );

    // create the second table (call results)
    $content = array();
    $category_list = array( 'Not yet called', 'Call in progress' );
    foreach( $phone_call_class_name::get_enum_values( 'status' ) as $status )
      $category_list[] = ucfirst( $status );
    foreach( $category_list as $category )
    {
      $row = array( $category );
      foreach( $this->category_totals_list as $site => $totals )
        $row[] = $totals[$category];
      $row[] = array_sum( $row );
      $content[] = $row;     
    }

    $this->add_table( 'Interviews in Progress', $header, $content, $footer, NULL, array( 'A' ) );

    // create the third table (permanent conditions)
    $content = array();
    $category_list = array();
    $state_mod = lib::create( 'database\modifier' );
    $state_mod->order( 'rank' );
    foreach( $state_class_name::select( $state_mod ) as $db_state )
      $category_list[] = ucfirst( $db_state->name );
    foreach( $category_list as $category )
    {
      $row = array( $category );
      foreach( $this->category_totals_list as $site => $totals )
        $row[] = $totals[$category];
      $row[] = array_sum( $row );
      $content[] = $row;     
    }

    $this->add_table( 'Permanent Conditions', $header, $content, $footer, NULL, array( 'A' ) );

    // create the fourth table (additional information)
    $content = array();
    $category_list = array(
      'Response rate', 
      'Total number of calls', 
      );
    foreach( $category_list as $category )
    {
      $row = array( $category );
      foreach( $this->category_totals_list as $site => $totals )
        $row[] = $totals[$category];
      $row[] = array_sum( $row );
      $content[] = $row;     
    }

    // total response rate is avergage, not sum
    $sum = array_pop( $content[0] );
    // count all but the first and last
    $total = 0;
    for( $i = 1; $i < count( $content[0] ); $i++ ) if( 0 < $content[0][$i] ) $total++;
    $content[0][] = $sum / $total;
    $this->add_table( 'Additional Information', $header, $content, NULL, NULL, array( 'A' ) );
  }

  /**
   * Internal function for setting the category totals for this report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $sub_category The name of the sub-category to set
   * @param database\modifier $modifier The modifier to apply to the query
   * @access private
   */
  private function set_category_totals( $sub_category, $modifier = NULL )
  {
    $record_class_name = lib::get_class_name( 'database\record' );

    // get the count for each category
    $rows = $record_class_name::db()->get_all(
      sprintf( 'SELECT temp_participant.category, temp_participant.id FROM temp_participant %s',
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
}
