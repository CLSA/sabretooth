<?php
/**
 * call_history.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business\report;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Mailout required report data.
 * 
 * @abstract
 */
class call_history extends \cenozo\business\report\base_report
{
  /**
   * Build the report
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $phone_call_class_name = lib::get_class_name( 'database\phone_call' );

    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'assignment', 'phone_call.assignment_id', 'assignment.id' );
    $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
    $modifier->join( 'participant', 'interview.participant_id', 'participant.id' );
    $modifier->left_join( 'site', 'assignment.site_id', 'site.id' );
    $modifier->left_join( 'age_group', 'participant.age_group_id', 'age_group.id' );
    $modifier->join( 'user', 'assignment.user_id', 'user.id' );
    $modifier->order( 'phone_call.start_datetime' );

    $report_restriction_sel = lib::create( 'database\select' );
    $report_restriction_sel->add_table_column( 'report_has_report_restriction', 'value' );
    $report_restriction_sel->add_column( 'name' );
    $report_restriction_sel->add_column( 'restriction_type' );
    $report_restriction_sel->add_column( 'subject' );
    $report_restriction_sel->add_column( 'operator' );
    $report_restriction_mod = lib::create( 'database\modifier' );
    $report_restriction_mod->where( 'custom', '=', true );
    $report_restriction_mod->or_where( 'subject', '=', 'site' );
    $restriction_list =
      $this->db_report->get_report_restriction_list( $report_restriction_sel, $report_restriction_mod );

    foreach( $restriction_list as $restriction )
    {
      if( 'site' == $restriction['name'] )
      {
        $modifier->where( 'assignment.site_id', '=', $restriction['value'] );
      }
      else if( 'last_call' == $restriction['name'] && $restriction['value'] )
      {
        // join to assignment_last_phone_call so that only the last call is included in the query
        $join_mod = lib::create( 'database\modifier' );
        $join_mod->where( 'assignment.id', '=', 'assignment_last_phone_call.assignment_id', false );
        $join_mod->where( 'phone_call.id', '=', 'assignment_last_phone_call.phone_call_id', false );
        $modifier->join_modifier( 'assignment_last_phone_call', $join_mod );
      }
    }
    
    // set up requirements
    $this->apply_restrictions( $modifier );

    $select = lib::create( 'database\select' );
    $select->from( 'phone_call' );
    if( $this->db_role->all_sites ) $select->add_table_column( 'site', 'name', 'Site' );
    $select->add_table_column( 'participant', 'uid', 'UID' );
    $select->add_table_column( 'participant', 'sex', 'Sex' );
    $select->add_table_column( 'age_group', 'CONCAT( lower, " to ", upper )', 'Age Group', false );
    $select->add_table_column( 'user', 'CONCAT( user.first_name, " ", user.last_name )', 'User', false );
    $select->add_table_column( 'assignment', 'id', 'Assignment ID' );
    $select->add_column( 'DATE( phone_call.start_datetime )', 'Date', false );
    $select->add_column( 'TIME( phone_call.start_datetime )', 'Call Start', false );
    $select->add_column( 'TIME( phone_call.end_datetime )', 'Call End', false );
    $select->add_column( 'TIMEDIFF( phone_call.end_datetime, phone_call.start_datetime )', 'Elapsed', false );
    $select->add_column( 'status', 'Call Result' );

    $header = array();
    $content = array();
    $sql = sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() );

    $this->add_table_from_select( NULL, $phone_call_class_name::select( $select, $modifier ) );
  }
}
