<?php
/**
 * phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * phase: record
 *
 * @package sabretooth\database
 */
class phase extends has_rank
{
  /**
   * Overrides the parent class so manage ranks.
   * 
   * If the record has a rank which already exists it will push the current record and all that
   * come after it down by one rank to make room for this one.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function save()
  {
    parent::save();

    // make sure that we are auditing this phase's survey
    $this->ensure_auditing();
  }
  
  /**
   * Returns this phase's survey.
   * This overrides the parent's magic method because the survey record is outside the main db.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return surveys
   * @access public
   */
  public function get_survey()
  {
    // check the primary key value
    $primary_key_name = static::get_primary_key_name();
    if( is_null( $this->$primary_key_name ) )
    {
      log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    return new limesurvey\surveys( $this->sid );
  }
  
  /**
   * If auditing is enabled this method creates the audit table and trigger for the survey
   * associated with this phase (if they do not already exist).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function ensure_auditing()
  {
    // ignore if auditing is not enabled
    $setting_manager = bus\setting_manager::self();
    if( !$setting_manager->get_setting( 'audit_db', 'enabled' ) ) return;

    // check the primary key value
    $primary_key_name = static::get_primary_key_name();
    if( is_null( $this->$primary_key_name ) )
    {
      log::warning( 'Cannot ensure auditing for phase with no id.' );
      return;
    }
    
    // check the sid
    if( is_null( $this->sid ) )
    {
      log::warning( 'Cannot ensure auditing for phase with no sid.' );
      return;
    }
    
    $survey_table = $setting_manager->get_setting( 'survey_db', 'prefix' ).'survey_'.$this->sid;
    $audit_table = $setting_manager->get_setting( 'audit_db', 'prefix' ).'survey_'.$this->sid;

    // check to see if the audit table already exists
    $count = static::db()->get_one( sprintf(
      ' SELECT COUNT(*)'.
      ' FROM information_schema.TABLES'.
      ' WHERE TABLE_SCHEMA = %s'.
      ' AND TABLE_NAME = %s',
      database::format_string( $setting_manager->get_setting( 'audit_db', 'database' ) ),
      database::format_string( $audit_table ) ) );
    
    if( 0 == $count )
    {
      $audit_db = bus\session::self()->get_audit_database();
      $survey_db = bus\session::self()->get_survey_database();

      // get the survey table's create syntax
      $row = $survey_db->get_row( 'SHOW CREATE TABLE '.$survey_table );
      $sql = $row['Create Table'];

      // add a timestamp
      $insert_pos = strpos( $sql, '`submitdate`' );
      $insert_sql =
        substr( $sql, 0, $insert_pos ).
        '`timestamp` timestamp NOT NULL'.
        " ON UPDATE CURRENT_TIMESTAMP,\n".
        substr( $sql, $insert_pos );

      // remove the auto increment
      $insert_sql = preg_replace( '/ AUTO_INCREMENT(=[0-9]+)?/', '', $insert_sql );

      // remove the primary key
      $insert_sql = str_replace( ",\nPRIMARY KEY (`id`)", '', $insert_sql );

      // set the table name
      $insert_sql = preg_replace( '/`'.$survey_table.'` \(\n/',
                                  '`'.$audit_table."` ( \n", $insert_sql );

      $audit_db->execute( $insert_sql );

      // TODO: need to add the trigger
    }
  }

  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'qnaire';
}
?>
