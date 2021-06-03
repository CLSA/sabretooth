<?php
/**
 * get.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\self;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the get meta-resource
 */
class get extends \cenozo\service\self\get
{
  /**
   * Override parent method since self is a meta-resource
   */
  protected function create_resource( $index )
  {
    $setting_manager = lib::create( 'business\setting_manager' );
    $session = lib::create( 'business\session' );
    $resource = parent::create_resource( $index );

    $setting_sel = lib::create( 'database\select' );
    $setting_sel->from( 'setting' );
    $setting_sel->add_all_table_columns();
    $resource['setting'] = $session->get_setting()->get_column_values( $setting_sel );
    $resource['setting']['proxy'] = $setting_manager->get_setting( 'general', 'proxy' );
    $resource['setting']['vacancy_size'] = $setting_manager->get_setting( 'general', 'vacancy_size' );

    return $resource;
  }
}
