<?php
/**
 * survey_timings.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Access to limesurvey's survey_SID_timings tables.
 */
class survey_timings extends sid_record
{
  /**
   * Need to override the parent class because this table doesn't follow the
   * <table_name>_<SID> convention.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   * @static
   */
  public static function get_table_name()
  {
    if( is_null( static::get_sid() ) )
    {
      throw lib::create( 'exception\runtime',
        'The survey id (table_sid) must be set before using this class.', __METHOD__ );
    }

    return sprintf( 'survey_%s_timings', static::get_sid() );
  }
  
  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'id';
}
?>
