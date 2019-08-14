<?php
/**
 * call_history.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Call history report
 */
class call_history extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @access protected
   */
  protected function build()
  {
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
    $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
    $modifier->join( 'qnaire', 'interview.qnaire_id', 'qnaire.id' );
    $modifier->join( 'script', 'qnaire.script_id', 'script.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->join( 'language', 'participant.language_id', 'language.id' );
    $modifier->left_join( 'site', 'assignment.site_id', 'site.id' );
    $modifier->left_join( 'age_group', 'participant.age_group_id', 'age_group.id' );
    $modifier->join( 'user', 'assignment.user_id', 'user.id' );
    $modifier->order( 'phone_call.start_datetime' );

    foreach( $this->get_restriction_list() as $restriction )
    {
      if( 'collection' == $restriction['name'] )
      {
        $modifier->join(
          'collection_has_participant', 'participant.id', 'collection_has_participant.participant_id' );
        $modifier->where( 'collection_has_participant.collection_id', '=', $restriction['value'] );
      }
      else if( 'qnaire' == $restriction['name'] )
      {
        $modifier->where( 'qnaire.id', '=', $restriction['value'] );
      }
      else if( 'last_call' == $restriction['name'] && $restriction['value'] )
      {
        // join to interview_last_assignment and assignment_last_phone_call so that only the interview's
        // last call is included in the query
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'interview.id', '=', 'interview_last_assignment.interview_id', false );
        $join_mod->where( 'assignment.id', '=', 'interview_last_assignment.assignment_id', false );
        $modifier->join_modifier( 'interview_last_assignment', $join_mod );
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'assignment.id', '=', 'assignment_last_phone_call.assignment_id', false );
        $join_mod->where( 'phone_call.id', '=', 'assignment_last_phone_call.phone_call_id', false );
        $modifier->join_modifier( 'assignment_last_phone_call', $join_mod );
      }
    }

    // we need to get the site restriction in order to restrict the assignment by its value
    $report_restriction_sel = lib::create( 'database\select' );
    $report_restriction_sel->add_table_column( 'report_has_report_restriction', 'value' );
    $report_restriction_sel->add_column( 'name' );
    $report_restriction_sel->add_column( 'restriction_type' );
    $report_restriction_sel->add_column( 'subject' );
    $report_restriction_sel->add_column( 'operator' );
    $report_restriction_mod = lib::create( 'database\modifier' );
    $report_restriction_mod->where( 'subject', '=', 'site' );
    $restriction_list =
      $this->db_report->get_report_restriction_list( $report_restriction_sel, $report_restriction_mod );

    if( 0 < count( $restriction_list ) )
    {
      $restriction = current( $restriction_list );
      $modifier->where( 'assignment.site_id', '=', $restriction['value'] );
    }

    // set up requirements
    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->from( 'phone_call' );
    if( $this->db_role->all_sites ) $select->add_column( 'site.name', 'Site', false );
    $select->add_column( 'participant.uid', 'UID', false );
    $select->add_column( 'language.name', 'Language', false );
    $select->add_column( 'participant.sex', 'Sex', false );
    $select->add_column( 'CONCAT( lower, " to ", upper )', 'Age Group', false );
    $select->add_column( 'participant.email', 'Email', false );
    $select->add_column( 'CONCAT( user.first_name, " ", user.last_name )', 'User', false );
    $select->add_column( 'assignment.id', 'Assignment ID', false );
    $select->add_column( 'script.name', 'Questionnaire', false );
    $select->add_column( $this->get_datetime_column( 'phone_call.start_datetime', 'date' ), 'Date', false );
    $select->add_column( $this->get_datetime_column( 'phone_call.start_datetime', 'time' ), 'Call Start', false );
    $select->add_column( $this->get_datetime_column( 'phone_call.end_datetime', 'time' ), 'Call End', false );
    $select->add_column( 'TIMEDIFF( phone_call.end_datetime, phone_call.start_datetime )', 'Elapsed', false );
    $select->add_column( 'status', 'Call Result' );

    $this->add_table_from_select( NULL, $phone_call_class_name::select( $select, $modifier ) );
  }
}
