<?php
/**
 * survey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Access to limesurvey's survey_SID tables.
 * 
 * @package sabretooth\database
 */
class survey extends sid_record
{
  public function get_response( $question_code )
  {
    // the questions table has more than one column in its primary key so custom sql is needed
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'sid', '=', static::get_sid() );
    $modifier->where( 'title', '=', $question_code );
    $modifier->group( 'sid' );
    $modifier->group( 'gid' );
    $modifier->group( 'qid' );
    $sql = sprintf( 'SELECT gid, qid FROM %s %s',
                    static::db()->get_prefix().'questions',
                    $modifier->get_sql() );
    
    $row = static::db()->get_row( $sql );
    if( 0 == count( $row ) )
      throw lib::create( 'exception\runtime', 'Question code not found in survey.', __METHOD__ );

    $column_name = sprintf( '%sX%sX%s', static::get_sid(), $row['gid'], $row['qid'] );
    return $this->$column_name;
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';
}
?>
