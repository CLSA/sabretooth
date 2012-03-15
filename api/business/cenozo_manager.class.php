<?php
/**
 * cenozo_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package cenozo\business
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's manager with custom methods
 * 
 * @package sabretooth\business
 */
class cenozo_manager extends \cenozo\business\cenozo_manager
{
  /**
   * Override the parent method to specify tracking as the cohort.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array& $arguments
   * @access protected
   */
  protected function set_site_and_role( &$arguments )
  {
    $session = lib::create( 'business\session' );
    $arguments['request_site_name'] = 'tracking////'.$session->get_site()->name;
    $arguments['request_role_name'] = $session->get_role()->name;
  }
}
