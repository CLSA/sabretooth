<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Appointment report
 */
class appointment extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $session = lib::create( 'business\session' );
    $db_study_phase = $session->get_application()->get_study_phase();
    $db_identifier = is_null( $db_study_phase ) ? NULL : $db_study_phase->get_study()->get_identifier();

    // get whether restricting by qnaire or site
    $db_site = NULL;
    $db_qnaire = NULL;
    foreach( $this->get_restriction_list() as $restriction )
    {
      if( 'qnaire' == $restriction['name'] ) $db_qnaire = lib::create( 'database\qnaire', $restriction['value'] );
      else if( 'site' == $restriction['name'] ) $db_site = lib::create( 'database\site', $restriction['value'] );
    }

    $modifier = lib::create( 'database\modifier' );
    $select = lib::create( 'database\select' );
    $select->from( $this->db_report->get_report_type()->subject );
    if( $this->db_role->all_sites ) $select->add_column( 'IFNULL( site.name, "(none)" )', 'Site', false );
    else $db_site = $this->db_site; // always restrict to the user's site if they don't have all-site access
    $select->add_column(
      'CONCAT_WS( " ", honorific, participant.first_name, CONCAT( "(", other_name, ")" ), participant.last_name )',
      'Name',
      false );
    $select->add_column( 'participant.uid', 'UID', false );
    if( !is_null( $db_identifier ) ) $select->add_column( 'participant_identifier.value', 'Study ID', false );
    if( is_null( $db_qnaire ) ) $select->add_column( 'script.name', 'Questionnaire', false );
    $select->add_column( $this->get_datetime_column( 'vacancy.datetime', 'date' ), 'Date', false );
    $select->add_column( $this->get_datetime_column( 'vacancy.datetime', 'time' ), 'Time', false );
    $select->add_column( 'TIMESTAMPDIFF( YEAR, participant.date_of_birth, CURDATE() )', 'Age', false );
    $select->add_column( 'participant.sex', 'Sex', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column(
      'IFNULL( appointment.outcome, IF( UTC_TIMESTAMP() < vacancy.datetime, "upcoming", "passed" ) )',
      'State',
      false
    );

    $modifier->join( 'vacancy', 'appointment.start_vacancy_id', 'vacancy.id' );
    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    if( !is_null( $db_qnaire ) ) $modifier->where( 'qnaire.id', '=', $db_qnaire->id );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    if( !is_null( $db_identifier ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'participant_identifier.participant_id', false );
      $join_mod->where( 'participant_identifier.identifier_id', '=', $db_identifier->id );
      $modifier->join_modifier( 'participant_identifier', $join_mod, 'left' );
    }
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->left_join( 'phone', 'appointment.phone_id', 'appointment_phone.id', 'appointment_phone' );
    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'phone.participant_id', false );
    $join_mod->where( 'phone.rank', '=', 1 );
    $modifier->join_modifier( 'phone', $join_mod, 'left' );

    $select->add_column( 'IFNULL( appointment_phone.number, phone.number )', 'Phone', false );
    $select->add_column( 'IFNULL( participant.email, "(none)" )', 'Email', false );

    // make sure the participant has consented to participate
    $modifier->join( 'participant_last_consent', 'participant.id', 'participant_last_consent.participant_id' );
    $modifier->join( 'consent_type', 'participant_last_consent.consent_type_id', 'consent_type.id' );
    $modifier->join( 'consent', 'participant_last_consent.consent_id', 'consent.id' );
    $modifier->where( 'consent_type.name', '=', 'participation' );
    $modifier->where( 'consent.accept', '=', true );

    $this->apply_restrictions( $modifier );

    // replace application.datetime restrictions with vacancy.datetime
    // (this does nothing if the restrictions were not selected)
    $column = sprintf(
      'DATE_FORMAT( CONVERT_TZ( appointment.datetime, "UTC", "%s" ), "%%Y-%%m-%%d" )',
      $this->db_user->timezone
    );
    $modifier->replace_where( $column, str_replace( 'appointment.datetime', 'vacancy.datetime', $column ) );

    if( !$modifier->has_join( 'participant_site' ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
      $join_mod->where( 'participant_site.application_id', '=', $this->db_application->id );
      $modifier->join_modifier( 'participant_site', $join_mod );
    }
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );
    if( !is_null( $db_site ) ) $modifier->where( 'site.id', '=', $db_site->id );

    $modifier->order( 'vacancy.datetime' );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
