<?php
/**
 * groups.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * groups: record
 * Note, this class does not extend database\limesurvey\record because it has more than one
 * column in its primary key.  Do not attempt to use this class like an active record.
 */
class groups
{
  public static function get_data( $modifier = NULL )
  {
    return lib::create( 'business\session' )->get_survey_database()->get_all( sprintf(
      'SELECT * FROM groups %s',
      !is_null( $modifier ) ? $modifier->get_sql() : '' ) );
  }
}
