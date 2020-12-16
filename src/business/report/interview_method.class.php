<?php
/**
 * interview_method.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Call history report
 */
class interview_method extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $db_application = lib::create( 'business\session' )->get_application();

    // get the selected qnaire
    $qnaire_id = NULL;
    foreach( $this->get_restriction_list() as $restriction )
      if( 'qnaire' == $restriction['name'] ) $qnaire_id = $restriction['value'];

    $modifier = lib::create( 'database\modifier' );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'application_has_participant.participant_id', false );
    $join_mod->where( 'application_has_participant.application_id', '=', $db_application->id );
    $join_mod->where( 'application_has_participant.datetime', '!=', NULL );
    $modifier->join_modifier( 'application_has_participant', $join_mod );

    $modifier->left_join( 'exclusion', 'participant.exclusion_id', 'exclusion.id' );
    $modifier->join( 'participant_last_hold', 'participant.id', 'participant_last_hold.participant_id' );
    $modifier->left_join( 'hold', 'participant_last_hold.hold_id', 'hold.id' );
    $modifier->left_join( 'hold_type', 'hold.hold_type_id', 'hold_type.id' );
    $modifier->join( 'participant_last_proxy', 'participant.id', 'participant_last_proxy.participant_id' );
    $modifier->left_join( 'proxy', 'participant_last_proxy.proxy_id', 'proxy.id' );
    $modifier->left_join( 'proxy_type', 'proxy.proxy_type_id', 'proxy_type.id' );
    $modifier->join( 'participant_last_trace', 'participant.id', 'participant_last_trace.participant_id' );
    $modifier->left_join( 'trace', 'participant_last_trace.trace_id', 'trace.id' );
    $modifier->left_join( 'trace_type', 'trace.trace_type_id', 'trace_type.id' );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'interview.participant_id', false );
    $join_mod->where( 'interview.qnaire_id', '=', $qnaire_id );
    $modifier->join_modifier( 'interview', $join_mod, 'left' );

    $modifier->join( 'language', 'participant.language_id', 'language.id' );

    $join_mod = lib::create( 'database\modifier' );
    $join_mod->where( 'participant.id', '=', 'participant_site.participant_id', false );
    $join_mod->where( 'participant_site.application_id', '=', $db_application->id );
    $modifier->join_modifier( 'participant_site', $join_mod );
    $modifier->left_join( 'site', 'participant_site.site_id', 'site.id' );

    // set up restrictions
    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_column( 'IFNULL( site.name, "(none)" )', 'Site', false );
    $select->add_column( 'participant.uid', 'UID', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column( 'participant.sex', 'Sex', false );
    $select->add_column( 'TIMESTAMPDIFF( YEAR, participant.date_of_birth, CURDATE() )', 'Age', false );
    $select->add_column(
      $participant_class_name::get_status_column_sql(),
      'Status',
      false
    );
    $select->add_column( 'IFNULL( interview.method, "phone" )', 'Method', false );

    $this->add_table_from_select( NULL, $participant_class_name::select( $select, $modifier ) );
  }
}
