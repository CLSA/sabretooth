<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * appointment: record
 */
class appointment extends \cenozo\database\record
{
  /**
   * Overrides the parent save method.
   * @author Patrick Emond
   * @access public
   */
  public function save()
  {
    $callback_class_name = lib::get_class_name( 'database\callback' );

    // make sure there is a maximum of 1 unassigned appointment or callback per interview
    if( is_null( $this->id ) && is_null( $this->assignment_id ) )
    {
      $appointment_mod = lib::create( 'database\modifier' );
      $appointment_mod->where( 'interview_id', '=', $this->interview_id );
      $appointment_mod->where( 'assignment_id', '=', NULL );
      if( !is_null( $this->id ) ) $appointment_mod->where( 'id', '!=', $this->id );

      $callback_mod = lib::create( 'database\modifier' );
      $callback_mod->where( 'interview_id', '=', $this->interview_id );
      $callback_mod->where( 'assignment_id', '=', NULL );

      if( 0 < static::count( $appointment_mod ) || 0 < $callback_class_name::count( $callback_mod ) )
        throw lib::create( 'exception\notice',
          'Cannot have more than one unassigned appointment or callback per interview.', __METHOD__ );
    }

    // if we changed certain columns then update the queue
    $update_queue = $this->has_column_changed( array( 'assignment_id', 'datetime' ) );
    parent::save();
    if( $update_queue ) $this->get_interview()->get_participant()->repopulate_queue( true );
  }

  /**
   * Override the parent method
   */
  public function delete()
  {
    $db_participant = $this->get_interview()->get_participant();
    parent::delete();
    $db_participant->repopulate_queue( true );
  }

  /**
   * Get the end datetime based on the appointments start, type and site's settings
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return DateTime
   * @access public
   */
  public function get_end_datetime()
  {
    if( is_null( $this->datetime ) )
      throw lib::create( 'exception\runtime',
        'Cannot get appointment end_datetime since the datetime isn\'t set.', __METHOD__ );
    if( is_null( $this->type ) )
      throw lib::create( 'exception\runtime',
        'Cannot get appointment end_datetime since the type isn\'t set.', __METHOD__ );

    // get the site the appointment refers to
    $db_site = NULL;
    $db_interview = $this->get_interview();
    if( !is_null( $db_interview ) )
    {
      $db_participant = $this->get_interview()->get_participant();
      $db_site = $db_participant->get_effective_site();
    }
    else // if the appointment isn't assigned to a participant then use the user's site
    {
      $db_site = lib::create( 'business\session' )->get_site();
    }

    $setting_sel = lib::create( 'database\select' );
    $setting_sel->add_column( 'short_appointment' );
    $setting_sel->add_column( 'long_appointment' );
    $setting_mod = lib::create( 'database\modifier' );
    $settings = current( $db_site->get_setting_list( $setting_sel ) );
    $interval = $settings[$this->type.'_appointment'];

    $datetime = util::get_datetime_object( $this->datetime );
    $datetime->add( new \DateInterval( sprintf( 'PT%dM', $interval ) ) );
    return $datetime;
  }

  /**
   * Determines whether there are operator operators available during this appointment's date/time
   * 
   * This method will start by counting how many operators have a shift scheduled over the appointment's
   * start/end period, then add shift templates if there are no operators, and finally it will remove
   * the number of existing appointments (other than this one) who's start/end period overlaps this one.
   * If the result is greater than 0 then this method will return true, otherwise false is returned.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @throws exception\runtime
   * @access public
   */
  public function validate_date()
  {
    if( is_null( $this->interview_id ) )
      throw lib::create( 'exception\runtime',
        'Cannot validate appointment date, interview id is not set.', __METHOD__ );

    // appointments which are overridden or with past dates are always allowed
    if( $this->override ||
        util::get_datetime_object( $this->datetime ) < util::get_datetime_object() ) return true;

    $session = lib::create( 'business\session' );
    $db_application = $session->get_application();

    // get the participant's site
    $db_participant = $this->get_interview()->get_participant();
    $db_site = $db_participant->get_effective_site();
    if( is_null( $db_site ) ) $db_site = $session->get_site();

    // get the site's long and short appointment durations
    $setting_sel = lib::create( 'database\select' );
    $setting_sel->add_column( 'short_appointment' );
    $setting_sel->add_column( 'long_appointment' );
    $settings = current( $db_site->get_setting_list( $setting_sel ) );

    // get the date of the appointment
    $date = util::get_datetime_object( $this->datetime );
    $day_of_week = $date->format( 'l' );
    $appointment_date = $date->format( 'Y-m-d' );

    // get the lower/upper datetime of the new and existing appointments as sql
    $new_lower_sql = static::db()->format_string( $this->datetime->format( 'Y-m-d H:i:s' ) );
    $new_upper_sql = sprintf( '%s + INTERVAL %d MINUTE', $new_lower_sql, $settings[$this->type.'_appointment'] );
    $old_lower_sql = 'datetime';
    $old_upper_sql =
      'datetime + INTERVAL IF( type = "long", setting.long_appointment, setting.short_appointment ) MINUTE';

    // get all shifts which could possibly fulfil this appointment
    $shift_sel = lib::create( 'database\select' );
    $shift_sel->from( 'shift' );
    $shift_sel->add_column( 'start_datetime' );
    $shift_sel->add_column( 'end_datetime' );
    $shift_mod = lib::create( 'database\modifier' );
    $shift_mod->where( 'start_datetime', '<=', $this->datetime );
    $shift_mod->where( 'end_datetime', '>=', $this->datetime );
    $shift_mod->order( 'start_datetime' );

    $operator_list = array();
    foreach( $db_site->get_shift_list( $shift_sel, $shift_mod ) as $shift )
    {
      $operator_list[] = array(
        'appointment' => NULL,
        'locked' => false,
        'start' => util::get_datetime_object( $shift['start_datetime'] ),
        'end' => util::get_datetime_object( $shift['end_datetime'] ) );
    }

    if( 0 == count( $operator_list ) ) // no shifts, get shift templates instead
    {
      $shift_template_sel = lib::create( 'database\select' );
      $shift_template_sel->from( 'shift_template' );
      $shift_template_sel->add_column(
        sprintf( 'CONCAT( "%s ", start_time ) - '.
                 'INTERVAL IF( TIME( CONVERT_TZ( "%s", "UTC", "%s" ) ) - '.
                              'TIME( CONVERT_TZ( "2000-01-01", "UTC", "%s" ) ), 1, 0 ) HOUR',
                 $appointment_date,
                 $appointment_date,
                 $db_site->timezone, 
                 $db_site->timezone ),
        'start_datetime', false );
      // add a day to the end datetime if the end is before the start (looping over midnight)
      $shift_template_sel->add_column(
        sprintf( 'CONCAT( "%s ", end_time ) + INTERVAL IF( end_time < start_time, 1, 0 ) DAY - '.
                 'INTERVAL IF( TIME( CONVERT_TZ( "%s", "UTC", "%s" ) ) - '.
                              'TIME( CONVERT_TZ( "2000-01-01", "UTC", "%s" ) ), 1, 0 ) HOUR',
                 $appointment_date,
                 $appointment_date,
                 $db_site->timezone, 
                 $db_site->timezone ),
        'end_datetime', false );
      $shift_template_sel->add_column( 'operators' );

      $shift_template_mod = lib::create( 'database\modifier' );

      // in the correct date span
      $shift_template_mod->where( 'start_date', '<=', $appointment_date );
      $shift_template_mod->where(
        sprintf( 'IFNULL( end_date, "%s" )', $appointment_date ), '>=', $appointment_date );

      // put all shift template types in a single where bracket
      $shift_template_mod->where_bracket( true );

      // weekly shift templates
      $shift_template_mod->where_bracket( true );
      $shift_template_mod->where( 'repeat_type', '=', 'weekly' );
      $shift_template_mod->where( $day_of_week, '=', true );
      $shift_template_mod->where(
        sprintf(
          'MOD( DATEDIFF( '.
            '"%s" - INTERVAL dayofweek( "%s" )-1 DAY, '.
            'start_date - INTERVAL dayofweek( start_date )-1 DAY '.
          ') / 7, repeat_every )',
          $appointment_date,
          $appointment_date
        ), '=', 0 );
      $shift_template_mod->where_bracket( false );

      // day of month shift templates
      $shift_template_mod->where_bracket( true, true );
      $shift_template_mod->where( 'repeat_type', '=', 'day of month' );
      $shift_template_mod->where(
        'DAYOFMONTH( start_date )', '=', sprintf( 'DAYOFMONTH( "%s" )', $appointment_date ), false );
      $shift_template_mod->where_bracket( false );

      // day of week shift templates
      $shift_template_mod->where_bracket( true, true );
      $shift_template_mod->where( 'repeat_type', '=', 'day of week' );
      $shift_template_mod->where(
        'DAYOFWEEK( start_date )', '=', sprintf( 'DAYOFWEEK( "%s" )', $appointment_date ), false );
      $shift_template_mod->where(
        'CEILING( DAYOFMONTH( start_date )/7 )', '=',
        sprintf( 'CEILING( DAYOFMONTH( "%s" )/7 )', $appointment_date ), false );
      $shift_template_mod->where_bracket( false );

      // end the shift template type where bracket
      $shift_template_mod->where_bracket( false );
      $shift_template_mod->order( 'start_time' );

      foreach( $db_site->get_shift_template_list( $shift_template_sel, $shift_template_mod ) as $shift_template )
      {
        // add the shift template's operators number of mock-operators to the operator list
        for( $i = 0; $i < $shift_template['operators']; $i++ )
          $operator_list[] = array(
            'appointment' => NULL,
            'locked' => false,
            'start' => util::get_datetime_object( $shift_template['start_datetime'] ),
            'end' => util::get_datetime_object( $shift_template['end_datetime'] ) );
      }
    }

    // now get all appointments which overlap THIS appointment
    $appointment_sel = lib::create( 'database\select' );
    $appointment_sel->from( 'appointment' );
    $appointment_sel->add_column( 'datetime', 'start_datetime' );
    $appointment_sel->add_column( $old_upper_sql, 'end_datetime', false );
    $appointment_mod = lib::create( 'database\modifier' );
    if( !is_null( $this->id ) ) $appointment_mod->where( 'appointment.id', '!=', $this->id );
    $appointment_mod->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'interview.participant_id', '=', 'participant_site.participant_id', false );
    $join_mod->where( 'participant_site.application_id', '=', $db_application->id, false );
    $appointment_mod->join_modifier( 'participant_site', $join_mod );
    $appointment_mod->where( 'participant_site.site_id', '=', $db_site->id );
    $appointment_mod->join( 'setting', 'participant_site.site_id', 'setting.site_id' );

    // restrict to appointments overlapping with this appointment
    $appointment_mod->where_bracket( true );

    // test if the start of any exiting appointment is inside the new appointment bounds
    $appointment_mod->where_bracket( true );
    $appointment_mod->where( $old_lower_sql, '>=', $new_lower_sql, false );
    $appointment_mod->where( $old_lower_sql, '<', $new_upper_sql, false );
    $appointment_mod->where_bracket( false );

    // test if the end of any exiting appointment is inside the new appointment bounds
    $appointment_mod->where_bracket( true, true );
    $appointment_mod->where( $old_upper_sql, '>', $new_lower_sql, false );
    $appointment_mod->where( $old_upper_sql, '<', $new_upper_sql, false );
    $appointment_mod->where_bracket( false );

    // test if the start of the new appointment is inside any existing appointment bounds
    $appointment_mod->where_bracket( true, true );
    $appointment_mod->where( $new_lower_sql, '>=', $old_lower_sql, false );
    $appointment_mod->where( $new_lower_sql, '<', $old_upper_sql, false );
    $appointment_mod->where_bracket( false );

    // test if the end of the new appointment is inside any existing appointment bounds
    $appointment_mod->where_bracket( true, true );
    $appointment_mod->where( $new_upper_sql, '>', $old_lower_sql, false );
    $appointment_mod->where( $new_upper_sql, '<', $old_upper_sql, false );
    $appointment_mod->where_bracket( false );

    $appointment_mod->where_bracket( false );
    $appointment_mod->order( 'datetime' );

    // We now have a list of available operators and appointments which need to be assigned
    // to each operator in the most optimal fashion (ie: such that they all get called)
    $appointment_list = array();
    foreach( static::select( $appointment_sel, $appointment_mod ) as $appointment )
    {
      $appointment_list[] = array(
        'start' => util::get_datetime_object( $appointment['start_datetime'] ),
        'end' => util::get_datetime_object( $appointment['end_datetime'] ) );
    }

    // (don't forget to add THIS appointment as well
    $appointment_list[] = array(
      'start' => util::get_datetime_object( $this->datetime ),
      'end' => $this->get_end_datetime() );

    while( 0 < count( $appointment_list ) )
    {
      // get the first appointment in the remaining list
      $appointment = array_pop( $appointment_list );
      $assigned = false;

      // and try to find an empty operator to pair it with
      foreach( $operator_list as $index => $operator )
      {
        if( is_null( $operator['appointment'] ) &&
            $appointment['start'] >= $operator['start'] &&
            $appointment['end'] <= $operator['end'] )
        {
          $operator_list[$index]['appointment'] = $appointment;
          $operator_list[$index]['locked'] = false;
          $assigned = true;
          break;
        }
      }

      if( !$assigned )
      {
        // if no operators match then look for operators who are already assigned but not locked
        foreach( $operator_list as $index => $operator )
        {
          if( !is_null( $operator['appointment'] ) &&
              !$operator['locked'] &&
              $appointment['start'] >= $operator['start'] &&
              $appointment['end'] <= $operator['end'] )
          {
            // swap out the unlocked appointment and lock the current one here
            $appointment_list[] = $operator_list[$index]['appointment'];
            $operator_list[$index]['appointment'] = $appointment;
            $operator_list[$index]['locked'] = true;
            $assigned = true;
            break;
          }
        }
      }

      // there is no way to assign this appointment to an operator
      if( !$assigned ) return false;
    }

    // all appointments have been assigned to an operator, so THIS appointment fits
    return true;
  }

  /**
   * Get the state of the appointment as a string:
   *   reached: the appointment was met and the participant was reached
   *   not reached: the appointment was met but the participant was not reached
   *   upcoming: the appointment's date/time has not yet occurred
   *   assignable: the appointment is ready to be assigned, but hasn't been
   *   missed: the appointment was missed (never assigned) and the call window has passed
   *   incomplete: the appointment was assigned but the assignment never closed (an error)
   *   assigned: the appointment is currently assigned
   *   in progress: the appointment is currently assigned and currently in a call
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_state()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine state for appointment with no primary key.' );
      return NULL;
    }

    // if the appointment's reached column is set, nothing else matters
    if( !is_null( $this->reached ) ) return $this->reached ? 'reached' : 'not reached';

    $db_participant = lib::create( 'database\participant', $this->get_interview()->participant_id );
    $db_setting = $db_participant->get_effective_site()->get_setting();

    $status = 'unknown';

    // settings are in minutes, time() is in seconds, so multiply by 60
    $pre_window_time = 60 * $db_setting->pre_call_window;
    $post_window_time = 60 * $db_setting->post_call_window;
    $now = util::get_datetime_object()->getTimestamp();
    $appointment = $this->datetime->getTimestamp();

    // get the status of the appointment
    $db_assignment = $this->get_assignment();
    if( !is_null( $db_assignment ) )
    {
      if( !is_null( $db_assignment->end_datetime ) )
      { // assignment closed but appointment never completed
        log::crit(
          sprintf( 'Appointment %d has assignment which is closed but no status was set.',
                   $this->id ) );
        $status = 'incomplete';
      }
      else // assignment active
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'end_datetime', '=', NULL );
        $open_phone_calls = $db_assignment->get_phone_call_count( $modifier );
        if( 0 < $open_phone_calls )
        { // assignment currently on call
          $status = "in progress";
        }
        else
        { // not on call
          $status = "assigned";
        }
      }
    }
    else if( $now < $appointment - $pre_window_time )
    {
      $status = 'upcoming';
    }
    else if( $now < $appointment + $post_window_time )
    {
      $status = 'assignable';
    }
    else
    {
      $status = 'missed';
    }

    return $status;
  }
}
