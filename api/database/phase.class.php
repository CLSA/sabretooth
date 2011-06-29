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
    
    $survey_db_name = $setting_manager->get_setting( 'survey_db', 'database' );
    $audit_db_name = $setting_manager->get_setting( 'audit_db', 'database' );
    $survey_table = $setting_manager->get_setting( 'survey_db', 'prefix' ).'survey_'.$this->sid;
    $audit_table = $setting_manager->get_setting( 'audit_db', 'prefix' ).'survey_'.$this->sid;
    $audit_db = bus\session::self()->get_audit_database();
    $survey_db = bus\session::self()->get_survey_database();

    // check to see if the audit table already exists
    $count = static::db()->get_one( sprintf(
      ' SELECT COUNT(*)'.
      ' FROM information_schema.TABLES'.
      ' WHERE TABLE_SCHEMA = %s'.
      ' AND TABLE_NAME = %s',
      database::format_string( $audit_db_name ),
      database::format_string( $audit_table ) ) );
    
    if( 0 == $count )
    {
      // get the survey table's create syntax
      $row = $survey_db->get_row( 'SHOW CREATE TABLE '.$survey_table );
      $sql = $row['Create Table'];

      // add a timestamp
      $insert_pos = strpos( $sql, '`submitdate`' );
      $insert_sql =
        substr( $sql, 0, $insert_pos ).
        '`timestamp` timestamp NOT NULL, '.
        substr( $sql, $insert_pos );
      
      // remove extra whitespace
      $insert_sql = preg_replace( '/[\n\r]/', '', $insert_sql );
      $insert_sql = preg_replace( '/ +/', ' ', $insert_sql );

      // remove the auto increment
      $insert_sql = preg_replace( '/ AUTO_INCREMENT(=[0-9]+)?/', '', $insert_sql );
      
      // remove the primary key
      $insert_sql = str_replace( ', PRIMARY KEY (`id`)', '', $insert_sql );

      // set the table name
      $insert_sql = str_replace( '`'.$survey_table.'` (',
                                 '`'.$audit_table.'` (', $insert_sql );
      
      $audit_db->execute( $insert_sql );
    }

    // check to see if the audit trigger already exists
    $count = static::db()->get_one( sprintf(
      ' SELECT COUNT(*)'.
      ' FROM information_schema.TRIGGERS'.
      ' WHERE TRIGGER_SCHEMA = %s'.
      ' AND TRIGGER_NAME = %s',
      database::format_string( $survey_db_name ),
      database::format_string( $survey_table.'_auditing' ) ) );
    
    if( 0 == $count )
    {
      // now we add the trigger
      $column_names = $survey_db->get_col(
        sprintf( ' SELECT COLUMN_NAME'.
                 ' FROM information_schema.COLUMNS'.
                 ' WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
                 database::format_string( $audit_db_name ),
                 database::format_string( $survey_table ) ) );
      
      // build the trigger sql
      $trigger_sql = sprintf( ' CREATE TRIGGER %s_auditing'.
                              ' BEFORE UPDATE ON %s FOR EACH ROW'.
                              ' BEGIN INSERT INTO %s.%s SET',
                              $survey_table,
                              $survey_table,
                              $audit_db_name,
                              $audit_table );
      
      $first = true;
      foreach( $column_names as $column_name )
      {
        $trigger_sql .= sprintf( '%s %s = OLD.%s',
                                 $first ? '' : ',',
                                 $column_name,
                                 $column_name );
        $first = false;
      }
      $trigger_sql .= '; END';
  
      $survey_db->execute( $trigger_sql );
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
