<?php
/**
 * survey.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Access to limesurvey's survey_SID tables.
 * 
 * Since limesurvey's database structure for the survey tables is dynamic this class overrides
 * much of the functionality in record class as is appropriate.
 * 
 * @package sabretooth\database
 */
class survey extends record
{
  /**
   * Returns the name of the table associated with this record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   * @static
   */
  public static function get_table_name()
  {
    if( is_null( static::$table_sid ) )
    {
      throw new exc\runtime(
        'The survey id (table_sid) must be set before using this class.', __METHOD__ );
    }

    return sprintf( '%s_%s',
                    parent::get_table_name(),
                    static::$table_sid );
  }
  
  public function get_response( $question_code )
  {
    // the questions table has more than one column in its primary key so custom sql is needed
    $modifier = new db\modifier();
    $modifier->where( 'sid', '=', static::$table_sid );
    $modifier->where( 'title', '=', $question_code );
    $modifier->group( 'sid' );
    $modifier->group( 'gid' );
    $modifier->group( 'qid' );
    $sql = sprintf( 'SELECT gid, qid FROM %s %s',
                    static::db()->get_prefix().'questions',
                    $modifier->get_sql() );
    
    $row = static::db()->get_row( $sql );
    $column_name = sprintf( '%sX%sX%s', static::$table_sid, $row['gid'], $row['qid'] );
    return $this->$column_name;
  }

  /**
   * The current survey table's sid.  Be sure to set this before calling the class constructor.
   * @var int
   * @access public
   */
  public static $table_sid = NULL;

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';
}
?>
