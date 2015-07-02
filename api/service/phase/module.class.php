<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\phase;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // join to limesurvey tables to get the survey name
    if( $select->has_column( 'survey_name' ) )
    {
      $setting_manager = lib::create( 'business\setting_manager' );
      $lsdb = $setting_manager->get_setting( 'survey_db', 'database' );

      $join_mod = lib::create( 'database\modifier' );
      $join_mod->where( 'phase.sid', '=', 'surveyls_survey_id', false );
      $join_mod->where( 'surveyls_language', '=', 'en' );
      $modifier->join_modifier( $lsdb.'.surveys_languagesettings', $join_mod );

      $select->add_table_column( 'surveys_languagesettings', 'surveyls_title', 'survey_name' );
    }
  }
}
