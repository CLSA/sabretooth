<?php
/**
 * user_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: user new
 *
 * Create a new user.
 */
class user_new extends \cenozo\ui\push\user_new
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
    if( array_key_exists( 'columns', $args['noid'] ) &&
        array_key_exists( 'site', $args['noid']['columns'] ) )
      $args['noid']['columns']['site']['cohort'] =
        lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );
    return $args;
  }
}
?>
