<?php
/**
 * productivity.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $activity_class_name = lib::get_class_name( 'database\activity' );
    $survey_class_name = lib::get_class_name( 'database\limesurvey\survey' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $interview_class_name = lib::get_class_name( 'database\interview' );

    // create a list of all qnaires
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $qnaire_mod->order( 'script.name' );
    $qnaire_list = array();
    foreach( $qnaire_class_name::select_objects( $qnaire_mod ) as $db_qnaire ) $qnaire_list[] = $db_qnaire;

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'interview_last_assignment', 'interview.id', 'interview_last_assignment.interview_id' );
    $modifier->join( 'assignment', 'interview_last_assignment.assignment_id', 'assignment.id' );
    $modifier->join( 'user', 'assignment.user_id', 'user.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    $modifier->where( 'interview.end_datetime', '!=', NULL );
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

    // set up requirements
    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->from( 'interview' );
    $select->add_table_column( 'user', 'id', 'user_id' );
    $select->add_table_column( 'user', 'name', 'user' );
    $select->add_table_column( 'script', 'name', 'script' );
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
            foreach( array( '', ' CompPH', ' Avg Length' ) as $type )
              foreach( $qnaire_list as $db_qnaire )
                $data[$row['user']][($db_qnaire->get_script()->name).$type] = 0;
            $data[$row['user']]['Total Time'] = $row['time'];
            if( $single_date )
            {
              $data[$row['user']]['Start Time'] = $row['start'];
              $data[$row['user']]['End Time'] = $row['end'];
            }
          }
          else
          {
            // extend the end time to 
            if( $single_date ) $data[$row['user']]['End Time'] = $row['end'];
            $data[$row['user']]['Total Time'] += $row['time'];
          }
        }
      }

      $sub_mod = clone $modifier;
      $sub_mod->where( 'interview.site_id', '=', $site['id'] );
      foreach( $interview_class_name::select( $select, $sub_mod ) as $row )
      {
        if( !array_key_exists( $row['user'], $data ) )
        {
          foreach( array( '', ' CompPH', ' Avg Length' ) as $type )
            foreach( $qnaire_list as $db_qnaire )
              $data[$row['user']][($db_qnaire->get_script()->name).$type] = 0;
          $data[$row['user']]['Total Time'] = 0;
          if( $single_date )
          {
            $data[$row['user']]['Start Time'] = '';
            $data[$row['user']]['End Time'] = '';
          }
        }

        $data[$row['user']][$row['script']] += $row['total'];
        foreach( $qnaire_list as $db_qnaire )
        {
          $old_sid = $survey_class_name::get_sid();
          $survey_class_name::set_sid( $db_qnaire->get_script()->sid );

          // get script time for this user
          $token_sel = lib::create( 'database\select' );
          $token_sel->add_column( 'uid' );
          $token_mod = lib::create( 'database\modifier' );
          $token_mod->join( 'interview', 'participant.id', 'interview.participant_id' );
          $token_mod->join(
            'interview_last_assignment', 'interview.id', 'interview_last_assignment.interview_id' );
          $token_mod->join( 'assignment', 'interview_last_assignment.assignment_id', 'assignment.id' );
          $token_mod->where( 'assignment.user_id', '=', $row['user_id'] );
          $token_mod->where( 'assignment.site_id', '=', $site['id'] );
          $token_mod->where( 'interview.qnaire_id', '=', $db_qnaire->id );
          $token_mod->order( 'uid' );

          $token_list = array();
          foreach( $participant_class_name::select( $token_sel, $token_mod ) as $participant )
            $token_list[] = $participant['uid'];

          $survey_time_mod = lib::create( 'database\modifier' );
          $survey_time_mod->where( 'token', 'IN', $token_list );
          $data[$row['user']][$db_qnaire->get_script()->name.' Avg Length'] =
            $survey_class_name::get_total_time( $survey_time_mod );

          $survey_class_name::set_sid( $old_sid );
        }
      }

      // add the completes per hour (CompPH)
      foreach( $data as $user => $row )
      {
        // convert the total time to minutes
        $data[$user]['Total Time'] = sprintf( '%0.2f', $row['Total Time'] / 60 );

        foreach( $qnaire_list as $db_qnaire )
        {
          $script = $db_qnaire->get_script()->name;
          $comp_name = $script.' CompPH';
          $avg_name = $script.' Avg Length';
          $data[$user][$comp_name] = 0 < $row['Total Time']
                                   ? sprintf( '%0.2f', $row[$script] / $row['Total Time'] )
                                   : '';
          $data[$user][$avg_name] = 0 < $row[$script]
                                  ? sprintf( '%0.2f', $row[$avg_name] / $row[$script] / 60 )
                                  : '';
        }
      }

      // create a table from this site's data
      $header = array_keys( $data );
      array_unshift( $header, '' );
      $contents = array();

      $first = true;
      foreach( $data as $user => $user_data )
      {
        if( $first )
        {
          foreach( $user_data as $key => $value ) $contents[$key] = array( $key, $value );
          $first = false;
        }
        else foreach( $user_data as $key => $value ) $contents[$key][] = $value;
      }
      $this->add_table( $site['name'], $header, $contents );
    }
  }
}
