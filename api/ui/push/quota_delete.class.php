<?php
/**
 * quota_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: quota delete
 */
class quota_delete extends \cenozo\ui\push\base_delete
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'quota', $args );
  }

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
   * Converts primary keys to unique keys in operation arguments.
   * All converted arguments will appear in the array under a 'noid' key.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An argument list, usually those passed to the push operation.
   * @return array
   * @access protected
   */
  protected function convert_to_noid( $args )
  {
    $args = parent::convert_to_noid( $args );

    // add in the quota's cohort
    $args['noid']['quota']['cohort'] =
      lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );

    return $args;
  }
}
?>
