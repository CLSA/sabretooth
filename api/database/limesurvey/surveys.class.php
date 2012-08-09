<?php
/**
 * surveys.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * surveys: record
 */
class surveys extends record
{
  /**
   * Gets the survey's title in the base language.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function get_title()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'sid', '=', $this->sid );
    $modifier->where( 'sid', '=', 'surveyls_survey_id', false );
    $modifier->where( 'language', '=', 'surveyls_language', false );

    // get the title from the survey's main language
    return static::db()->get_one(
      sprintf( 'SELECT surveyls_title FROM surveys_languagesettings, %s %s',
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
