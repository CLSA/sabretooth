<?php
/**
 * base_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's base class for all record "edit" push operations.
 */
abstract class base_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Override the parent method to add the service name to the site key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An argument list, usually those passed to the push operation.
   * @return array
   * @access protected
   */
  protected function convert_to_noid( $args )
  {
    $args = parent::convert_to_noid( $args );
    if( array_key_exists( 'columns', $args['noid'] ) &&
        array_key_exists( 'site', $args['noid']['columns'] ) &&
        is_array( $args['noid']['columns']['site'] ) )
      $args['noid']['columns']['site']['service_id'] = array( 'name' => 
        lib::create( 'business\setting_manager' )->get_setting( 'general', 'application_name' ) );
    return $args;
  }
}
?>
