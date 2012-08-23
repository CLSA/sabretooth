<?php
/**
 * user_new_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: user new_access
 */
class user_new_access extends \cenozo\ui\push\user_new_access
{
  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

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
    foreach( $args['noid']['site_list'] as $key => $value )
      $args['noid']['site_list'][$key]['cohort'] =
        lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );
    return $args;
  }
}
?>
