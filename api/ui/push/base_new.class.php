<?php
/**
 * base_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's base class for all record "new" push operations.
 */
abstract class base_new extends \cenozo\ui\push\base_new
{
  /**
   * Override the parent method to add the cohort to the site key.
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
      $args['noid']['columns']['site']['cohort'] =
        lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );
    return $args;
  }
}
?>
