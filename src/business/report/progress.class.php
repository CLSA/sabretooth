<?php
/**
 * progress.class.php
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
class progress extends \cenozo\business\report\base_report
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

    $modifier = lib::create( 'database\modifier' );
    $select = lib::create( 'database\select' );
    $select->from( 'participant' );
    $select->add_column( 'uid', 'UID' );

    // join to each interview for each qnaire
    $qnaire_sel = lib::create( 'database\select' );
    $qnaire_sel->add_column( 'id' );
    $qnaire_sel->add_table_column( 'script', 'name' );
    $qnaire_mod = lib::create( 'database\modifier' );
    $qnaire_mod->join( 'script', 'qnaire.script_id', 'script.id' );
    $qnaire_mod->order( 'qnaire.rank' );

    // restriction which qnaire to show if there is a restriction on qnaire
    $report_restriction_sel = lib::create( 'database\select' );
    $report_restriction_sel->add_table_column( 'report_has_report_restriction', 'value' );
    $report_restriction_sel->add_column( 'name' );
    $report_restriction_sel->add_column( 'restriction_type' );
    $report_restriction_sel->add_column( 'subject' );
    $report_restriction_sel->add_column( 'operator' );
    $report_restriction_mod = lib::create( 'database\modifier' );
    $report_restriction_mod->where( 'custom', '=', true );
    $restriction_list =
      $this->db_report->get_report_restriction_list( $report_restriction_sel, $report_restriction_mod );

    foreach( $restriction_list as $restriction )
      if( 'qnaire' == $restriction['name'] )
        $qnaire_mod->where( 'qnaire.id', '=', $restriction['value'] );

    foreach( $qnaire_class_name::select( $qnaire_sel, $qnaire_mod ) as $qnaire )
    {
      $interview = sprintf( 'interview_%d', $qnaire['id'] );
      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'participant.id', '=', $interview.'.participant_id', false );
      $join_mod->where( $interview.'.qnaire_id', '=', $qnaire['id'] );
      $modifier->join_modifier( 'interview', $join_mod, 'left', $interview );
      $select->add_column(
        $this->get_datetime_column( $interview.'.end_datetime' ), $qnaire['name'], false, 'string' );
    }

    // set up requirements
    $this->apply_restrictions( $modifier );

    $header = array();
    $content = array();
    $sql = sprintf( '%s %s', $select->get_sql(), $modifier->get_sql() );
    $rows = $participant_class_name::select( $select, $modifier );

    // create totals table
    if( count( $rows ) )
    {
      $totals = array_fill( 0, count( $rows[0] ), 0 );
      
      foreach( $rows as $row )
      {
        $index = 0;
        foreach( $row as $value )
        {
          if( $value ) $totals[$index]++;
          $index++;
        }
      }

      $header = array();
      foreach( $row as $column => $value ) $header[] = ucwords( str_replace( '_', ' ', $column ) );

      $this->add_table( 'Totals', $header, array( $totals ) );
    }

    $this->add_table_from_select( NULL, $rows );
  }
}
