<?php
/**
 * sample.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Progress report
 */
class sample extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $db = lib::create( 'business\session' )->get_database();
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );

    // create metadata for each qnaire
    $qnaire_sel = lib::create( 'database\select' );
    $qnaire_sel->add_column( 'id' );
    $qnaire_sel->add_table_column( 'script', 'name' );
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $qnaire_mod->order( 'qnaire.rank' );
    $qnaire_list = $qnaire_class_name::select( $qnaire_sel, $qnaire_mod );

    $appointment_column_list = array();
    foreach( $qnaire_list as $qnaire )
    {
      $interview_data = sprintf( 'interview_data_%d', $qnaire['id'] );

      $interview_data_sel = lib::create( 'database\select' );
      $interview_data_sel->from( 'interview' );
      $interview_data_sel->add_column( 'id' );
      $interview_data_sel->add_column(
        'IF( appointment.datetime < UTC_TIMESTAMP(), NULL, appointment.datetime )', 'datetime', false );
      $interview_data_sel->add_column( 'IF( phone_call.id IS NULL, 0, COUNT(*) )', 'total', false );
      $interview_data_mod = lib::create( 'database\modifier' );
      $interview_data_mod->join(
        'interview_last_appointment',
        'interview.id',
        'interview_last_appointment.interview_id'
      );
      $interview_data_mod->left_join(
        'appointment',
        'interview_last_appointment.appointment_id',
        'appointment.id'
      );
      $interview_data_mod->left_join( 'assignment', 'interview.id', 'assignment.interview_id' );
      $interview_data_mod->left_join( 'phone_call', 'assignment.id', 'phone_call.assignment_id' );
      $interview_data_mod->where( 'interview.qnaire_id', '=', $qnaire['id'] );
      $interview_data_mod->group( 'interview.id' );
      $db->execute( sprintf(
        "CREATE TEMPORARY TABLE %s\n%s %s",
        $interview_data,
        $interview_data_sel->get_sql(),
        $interview_data_mod->get_sql()
      ) );
      $db->execute( sprintf( 'ALTER TABLE %s ADD INDEX dk_id( id )', $interview_data ) );

      $appointment_column_list[] = $interview_data.'.datetime';
    }

    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_column( 'uid', 'UID' );
    if( $this->db_role->all_sites )
      $select->add_column( 'site.name', 'Site', false );
    $select->add_column( 'IF( participant.active, "Yes", "No" )', 'Active', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column( 'state.name', 'Condition', false );
    $select->add_column( $this->get_datetime_column( 'application_has_participant.datetime' ), 'Released', false );
    $select->add_column( 'IF( participant.email IS NOT NULL, "Yes", "No" )', 'Has Email', false );
    $select->add_column( 'region.name', 'Region', false );
    $select->add_column(
      $this->get_datetime_column( 'participant.callback' ),
      'Callback',
      false
    );
    $select->add_column(
      $this->get_datetime_column( sprintf( 'COALESCE( %s )', implode( ',', $appointment_column_list ) ) ),
      'Appointment',
      false
    );

    $modifier = lib::create( 'database\modifier' );
    $modifier->left_join( 'state', 'participant.state_id', 'state.id' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->join(
      'participant_primary_address', 'participant.id', 'participant_primary_address.participant_id' );
    $modifier->left_join( 'address', 'participant_primary_address.address_id', 'address.id' );
    $modifier->left_join( 'region', 'address.region_id', 'region.id' );

    // join to each interview for each qnaire
    $postfix = '';
    foreach( $qnaire_list as $qnaire )
    {
      $interview = sprintf( 'interview_%d', $qnaire['id'] );
      $interview_data = sprintf( 'interview_data_%d', $qnaire['id'] );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', $interview.'.participant_id', false );
      $join_mod->where( $interview.'.qnaire_id', '=', $qnaire['id'] );
      $modifier->join_modifier( 'interview', $join_mod, 'left', $interview );
      $modifier->left_join( $interview_data, $interview.'.id', $interview_data.'.id' );
      $select->add_column(
        $this->get_datetime_column( $interview.'.end_datetime' ), $qnaire['name'], false, 'string' );
      $select->add_column(
        sprintf( 'IF( %s.total IS NULL, 0, %s.total )', $interview_data, $interview_data ),
        'Phone Calls'.$postfix,
        false
      );
      $postfix .= ' ';
    }

    // set up requirements
    $this->apply_restrictions( $modifier );

    // create totals table
    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
