<?php
/**
 * productivity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Call history report
 */
class productivity extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $activity_class_name = lib::get_class_name( 'database\activity' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $interview_class_name = lib::get_class_name( 'database\interview' );
    $cenozo_manager = lib::create( 'business\cenozo_manager', lib::create( 'business\session' )->get_pine_application() );

    // create a list of all qnaires
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $qnaire_mod->order( 'script.name' );
    $qnaire_list = array();
    foreach( $qnaire_class_name::select_objects( $qnaire_mod ) as $db_qnaire ) $qnaire_list[] = $db_qnaire;
    $script_list = array();
    foreach( $qnaire_list as $db_qnaire ) $script_list[] = $db_qnaire->get_script();

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview_last_assignment', 'interview.id', 'interview_last_assignment.interview_id' );
    $modifier->join( 'assignment', 'interview_last_assignment.assignment_id', 'assignment.id' );
    $modifier->join( 'role', 'assignment.role_id', 'role.id' );
    $modifier->join( 'user', 'assignment.user_id', 'user.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    $modifier->where( 'interview.end_datetime', '!=', NULL );
    $modifier->where( 'interview.method', '=', 'phone' );
    $modifier->group( 'assignment.user_id' );
    $modifier->group( 'qnaire.id' );

    $base_activity_mod = lib::create( 'database\modifier' );

    $start_date = NULL;
    $end_date = NULL;
    $restrict_site_id = NULL;
    foreach( $this->get_restriction_list() as $restriction )
    {
      if( 'date' == $restriction['restriction_type'] )
      {
        $date = preg_replace( '/T.*/', '', $restriction['value'] );

        // keep track of the start/end date in case they match
        if( 'start_date' == $restriction['name'] ) $start_date = $date;
        else $end_date = $date;

        $modifier->where(
          sprintf( 'DATE( CONVERT_TZ( interview.end_datetime, "UTC", "%s" ) )', $this->db_user->timezone ),
          'start_date' == $restriction['name'] ? '>=' : '<=',
          $date
        );
        $base_activity_mod->where(
          sprintf( 'DATE( CONVERT_TZ( activity.start_datetime, "UTC", "%s" ) )', $this->db_user->timezone ),
          'start_date' == $restriction['name'] ? '>=' : '<=',
          $date
        );
      }
      else if( 'site' == $restriction['name'] )
      {
        $restrict_site_id = $restriction['value'];
      }
    }

    $single_date = !is_null( $start_date ) && !is_null( $end_date ) && $start_date == $end_date;

    // set up restrictions
    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->from( 'interview' );
    $select->add_column( 'qnaire_id' );
    $select->add_column( 'user.id', 'user_id', false );
    $select->add_column( 'user.name', 'user', false );
    $select->add_column( 'script.name', 'script', false );
    $select->add_column( 'COUNT(*)', 'total', false );

    $site_sel = lib::create( 'database\select' );
    $site_sel->add_column( 'id' );
    $site_sel->add_column( 'name' );
    $site_mod = lib::create( 'database\modifier' );
    if( !is_null( $restrict_site_id ) ) $site_mod->where( 'site.id', '=', $restrict_site_id );
    $site_mod->order( 'site.name' );

    foreach( $this->db_application->get_site_list( $site_sel, $site_mod ) as $site )
    {
      $data = array();

      // get the user's active time
      $activity_sel = lib::create( 'database\select' );
      $activity_sel->add_table_column( 'user', 'name', 'user' );
      if( $single_date )
      {
        $activity_sel->add_column( $this->get_datetime_column( 'start_datetime', 'time' ), 'start', false );
        $activity_sel->add_column( $this->get_datetime_column( 'end_datetime', 'time' ), 'end', false );
      }
      $activity_sel->add_column(
        'SUM( TIMESTAMPDIFF( MINUTE, activity.start_datetime, activity.end_datetime ) )',
        'time',
        false
      );

      $activity_mod = clone $base_activity_mod;
      $activity_mod->join( 'user', 'activity.user_id', 'user.id' );
      $activity_mod->join( 'role', 'activity.role_id', 'role.id' );
      $activity_mod->where( 'activity.application_id', '=', $this->db_application->id );
      $activity_mod->where( 'activity.site_id', '=', $site['id'] );
      $activity_mod->where( 'role.name', 'IN', array( 'operator', 'operator+' ) );
      $activity_mod->group( 'user.name' );
      $activity_mod->order( 'user.name' );
      $activity_mod->order( 'activity.start_datetime' );
      foreach( $activity_class_name::select( $activity_sel, $activity_mod ) as $row )
      {
        if( 0 < strlen( $row['user'] ) )
        {
          if( !array_key_exists( $row['user'], $data ) )
          {
            foreach( array( ' Completes', ' CompPH', ' Avg Length', ' Time' ) as $type )
              foreach( $script_list as $db_script )
                $data[$row['user']][($db_script->name).$type] = 0;
            $data[$row['user']]['Total Time'] = $row['time'];
            if( $single_date )
            {
              $data[$row['user']]['Start Time'] = $row['start'];
              $data[$row['user']]['End Time'] = $row['end'];
            }
          }
          else
          {
            if( $single_date ) $data[$row['user']]['End Time'] = $row['end'];
            $data[$row['user']]['Total Time'] += $row['time'];
          }
        }
      }

      $time_report = array( 'index' => array(), 'query' => array() );

      $has_pine_script = false;

      $sub_mod = clone $modifier;
      $sub_mod->where( 'interview.site_id', '=', $site['id'] );
      foreach( $interview_class_name::select( $select, $sub_mod ) as $row )
      {
        if( !array_key_exists( $row['user'], $data ) )
        {
          foreach( array( ' Completes', ' CompPH', ' Avg Length', ' Time' ) as $type )
            foreach( $script_list as $db_script )
              $data[$row['user']][($db_script->name).$type] = 0;
          $data[$row['user']]['Total Time'] = 0;
          if( $single_date )
          {
            $data[$row['user']]['Start Time'] = '';
            $data[$row['user']]['End Time'] = '';
          }
        }

        $data[$row['user']][$row['script'].' Completes'] += $row['total'];
        foreach( $qnaire_list as $index => $db_qnaire )
        {
          if( $row['qnaire_id'] == $db_qnaire->id )
          {
            $db_script = $script_list[$index];

            // setup the modifier that restricts to this user/site's assignments for this qnaire
            $interview_mod = lib::create( 'database\modifier' );
            $table_name = 'pine' != $db_script->get_type()
                        ? sprintf( '%s.interview_last_assignment', $participant_class_name::db()->get_name() )
                        : 'interview_last_assignment';
            $interview_mod->join( $table_name, 'interview.id', 'interview_last_assignment.interview_id' );
            $table_name = 'pine' != $db_script->get_type()
                        ? sprintf( '%s.assignment', $participant_class_name::db()->get_name() )
                        : 'assignment';
            $interview_mod->join( $table_name, 'interview_last_assignment.assignment_id', 'assignment.id' );
            $interview_mod->where( 'assignment.user_id', '=', $row['user_id'] );
            $interview_mod->where( 'assignment.site_id', '=', $site['id'] );
            $interview_mod->where( 'interview.qnaire_id', '=', $db_qnaire->id );

            // also restrict by date span, if requested
            if( !is_null( $start_date ) )
            {
              $interview_mod->where(
                sprintf( 'DATE( CONVERT_TZ( interview.end_datetime, "UTC", "%s" ) )', $this->db_user->timezone ),
                '>=',
                $start_date
              );
            }

            if( !is_null( $end_date ) )
            {
              $interview_mod->where(
                sprintf( 'DATE( CONVERT_TZ( interview.end_datetime, "UTC", "%s" ) )', $this->db_user->timezone ),
                '<=',
                $end_date
              );
            }
            
            if( 'pine' == $db_script->get_type() )
            {
              $has_pine_script = true;

              // for pine get a list of participants, below we'll fetch all data in a single request
              $interview_sel = lib::create( 'database\select' );
              $interview_sel->add_column( 'participant_id' );

              $participant_id_list = array();
              foreach( $interview_class_name::select( $interview_sel, $interview_mod ) as $r )
                $participant_id_list[] = $r['participant_id'];

              $data[$row['user']][$db_script->name.' Time'] = 0;
              if( 0 < count( $participant_id_list ) )
              {
                $time_report['index'][] = array(
                  'user' => $row['user'],
                  'column' => $db_script->name.' Time'
                );
                $time_report['query'][] = array(
                  'qnaire_id' => $db_script->pine_qnaire_id,
                  'participant_id_list' => $participant_id_list
                );
              }
            }
          }
        }
      }

      if( $has_pine_script )
      {
        // send all queries to pine as a single request to reduce machine-to-machine overhead
        if( 0 < count( $time_report['query'] ) )
        {
          $response = $cenozo_manager->post( 'respondent?time_report=1', $time_report['query'] );
          foreach( $response as $row )
          {
            // pine tracks time in seconds so we divide by 60 to convert to minutes
            $index = $time_report['index'][$row->index];
            $data[$index['user']][$index['column']] = $row->time / 60;
          }
        }
      }

      // track overall data for all scripts
      $overall_completes = array();
      $overall_time = array();
      $overall_total_time = 0;

      // add the completes per hour (CompPH)
      foreach( $data as $user => $row )
      {
        $data[$user]['Total Time'] = sprintf( '%0.2f', $row['Total Time'] );

        $user_total_time = 0;
        foreach( $qnaire_list as $db_qnaire )
        {
          $script = $db_qnaire->get_script()->name;
          $comp_name = $script.' Completes';
          $cph_name = $script.' CompPH';
          $time_name = $script.' Time';
          $avg_name = $script.' Avg Length';

          // keep track of the total time
          $user_total_time += $row[$time_name];

          // completes per hour === number of completes / total time (in minutes) * 60 (minutes/hour)
          $data[$user][$cph_name] = 0 < $row[$time_name]
                                   ? sprintf( '%0.2f', $row[$comp_name] / $row[$time_name] * 60 )
                                   : '';

          // average length === total time (in minutes) / number of completes
          $data[$user][$avg_name] = 0 < $row[$comp_name]
                                  ? sprintf( '%0.2f', $row[$time_name] / $row[$comp_name] )
                                  : '';

          // add to the overall data
          if( !array_key_exists( $script, $overall_completes ) ) $overall_completes[$script] = 0;
          $overall_completes[$script] += $row[$comp_name];
          if( !array_key_exists( $script, $overall_time ) ) $overall_time[$script] = 0;
          $overall_time[$script] += $row[$time_name];

          // remove the total time
          unset( $data[$user][$time_name] );
        }

        // remove user if all times are 0
        if( 0 == $user_total_time ) unset( $data[$user] );
        else $overall_total_time += $row['Total Time'];
      }

      // create a table from this site's data
      $header = array_keys( $data );
      array_unshift( $header, 'overall' );
      array_unshift( $header, '' );
      $contents = array();

      // first column has headings
      foreach( array( ' Completes', ' CompPH', ' Avg Length' ) as $type )
      {
        foreach( $qnaire_list as $db_qnaire )
        {
          $key = $db_qnaire->get_script()->name.$type;
          $contents[$key] = array( $key );
        }
      }
      $contents['Total Time'] = array( 'Total Time' );
      if( $single_date )
      {
        $contents['Start Time'] = array( 'Start Time' );
        $contents['End Time'] = array( 'End Time' );
      }

      // second column is overall data
      foreach( array( ' Completes', ' CompPH', ' Avg Length' ) as $type )
      {
        foreach( $qnaire_list as $db_qnaire )
        {
          $script = $db_qnaire->get_script()->name;
          $key = $script.$type;
          if( !array_key_exists( $script, $overall_completes ) ) $contents[$key][] = '';
          else
          {
            if( ' Completes' == $type ) $contents[$key][] = $overall_completes[$script];
            else if( ' CompPH' == $type ) $contents[$key][] = 0 < $overall_time[$script] ?
              sprintf( '%0.2f', $overall_completes[$script] / $overall_time[$script] * 60 ) : '';
            else if( ' Avg Length' == $type ) $contents[$key][] = 0 < $overall_completes[$script] ?
              sprintf( '%0.2f', $overall_time[$script] / $overall_completes[$script] ) : '';
          }
        }
      }
      $contents['Total Time'][] = $overall_total_time;
      if( $single_date )
      {
        $contents['Start Time'][] = '';
        $contents['End Time'][] = '';
      }

      foreach( $data as $user => $user_data )
        foreach( $user_data as $key => $value ) $contents[$key][] = $value;
      $this->add_table( $site['name'], $header, $contents );
    }
  }
}
