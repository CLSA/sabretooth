<?php
/**
 * base_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, cenozo\util;

/**
 * Extends Cenozo's base class for all record "edit" push operations.
 * 
 * @package sabretooth\ui
 */
abstract class base_edit extends \cenozo\ui\push\base_edit
{
  // TODO: document
  // add the cohort to the site column, if it exists
  protected function convert_to_noid( $args )
  {
    $args = parent::convert_to_noid( $args );
    if( array_key_exists( 'columns', $args['noid'] ) &&
        array_key_exists( 'site', $args['noid']['columns'] ) &&
        is_array( $args['noid']['columns']['site'] ) )
      $args['noid']['columns']['site']['cohort'] = 'tracking';
    return $args;
  }
}
?>
