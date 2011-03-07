<?php
/**
 * surveys.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;

/**
 * surveys: active record
 *
 * @package sabretooth\database
 */
class surveys extends active_record
{
  /**
   * Gets the survey's title in the base language.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_title()
  {
    $prefix = \sabretooth\session::self()->get_setting( 'survey_db', 'prefix' );
    
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'sid', '=', $this->sid );
    $modifier->where( 'surveyls_survey_id', '=', 'sid', false );
    $modifier->where( 'surveyls_language', '=', static::get_table_name().'.language', false );

    // get the title from the survey's main language
    return self::get_one(
      sprintf( 'SELECT surveyls_title FROM %s.%s, %s %s',
               static::get_database_name(),
               $prefix.'surveys_languagesettings',
               static::get_table_name(),
               $modifier->get_sql() ) );
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'sid';
}
?>
