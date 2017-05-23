<?php
/**
 * appointment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
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
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $qnaire_class_name = lib::get_class_name( 'database\qnaire' );
    $session = lib::create( 'business\session' );

    // get whether this is a site or home qnaire
    $db_qnaire = NULL;
    foreach( $this->get_restriction_list() as $restriction )
      if( 'qnaire' == $restriction['name'] )
        $db_qnaire = lib::create( 'database\qnaire', $restriction['value'] );

    $modifier = lib::create( 'database\modifier' );
    $select = lib::create( 'database\select' );
    $select->from( $this->db_report->get_report_type()->subject );
    if( $this->db_role->all_sites )
      $select->add_column( 'IFNULL( site.name, "(none)" )', 'Site', false );
    $select->add_column(
      'CONCAT_WS( " ", honorific, participant.first_name, CONCAT( "(", other_name, ")" ), participant.last_name )',
      'Name',
      false );
    $select->add_column( 'participant.uid', 'UID', false );
    if( is_null( $db_qnaire ) ) $select->add_column( 'script.name', 'Questionnaire', false );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'date' ), 'Date', false );
    $select->add_column( $this->get_datetime_column( 'appointment.datetime', 'time' ), 'Time', false );
    $select->add_column( 'TIMESTAMPDIFF( YEAR, participant.date_of_birth, CURDATE() )', 'Age', false );
    $select->add_column( 'participant.sex', 'Sex', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column(
      'IFNULL( appointment.outcome, IF( UTC_TIMESTAMP() < appointment.datetime, "upcoming", "passed" ) )',
      'State',
      false
    );

    $modifier->join( 'interview', 'appointment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    if( !is_null( $db_qnaire ) ) $modifier->where( 'qnaire.id', '=', $db_qnaire->id );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
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

    if( !$modifier->has_join( 'participant_site' ) )
    {
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
      $join_mod->where( 'participant_site.application_id', '=', $this->db_application->id );
      $modifier->join_modifier( 'participant_site', $join_mod );
    }
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );
    if( !$this->db_role->all_sites ) $modifier->where( 'site.id', '=', $this->db_site->id );

    $modifier->order( 'appointment.datetime' );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
