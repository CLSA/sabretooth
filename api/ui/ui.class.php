<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Application extension to ui class
 */
class ui extends \cenozo\ui\ui
{
  /**
   * Extends the parent method
   */
  public function get_lists( $modifier = NULL )
  {
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'service.subject', 'IN', array(
      'activity',
      'application',
      'collection',
      'language',
      'participant',
      'quota',
      'region_site',
      'site',
      'state',
      'system_message',
      'user' ) );

    return parent::get_lists( $modifier );
  }
}
