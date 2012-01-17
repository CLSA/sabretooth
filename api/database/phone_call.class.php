<?php
/**
 * phone_call.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * phone_call: record
 *
 * @package sabretooth\database
 */
class phone_call extends \cenozo\database\has_note
{
  /**
   * Identical to the parent's select method but restrict to a particular participant.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param participant $db_participant The participant to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_participant( $db_participant, $modifier = NULL, $count = false )
  {
    // if there is no site restriction then just use the parent method
    if( is_null( $db_participant ) ) return parent::select( $modifier, $count );

    // join to the assignment and interview tables
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'phone_call.assignment_id', '=', 'assignment.id', false );
    $modifier->where( 'assignment.interview_id', '=', 'interview.id', false );
    $modifier->where( 'interview.participant_id', '=', $db_participant->id );
    $sql = sprintf(
      ( $count ? 'SELECT COUNT(*) ' : 'SELECT phone_call.id ' ).
      'FROM phone_call, assignment, interview %s',
      $modifier->get_sql() );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular participant.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param participant $db_participant The participant to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_participant( $db_participant, $modifier = NULL )
  {
    return static::select_for_participant( $db_participant, $modifier, true );
  }
}
?>
