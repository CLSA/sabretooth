<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\site;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $db_application = lib::create( 'business\session' )->get_application();
    if( 'application' != $this->get_parent_subject() )
    {
      // only include sites which belong to this application
      if( !$modifier->has_join( 'application_has_site' ) )
        $modifier->join( 'application_has_site', 'site.id', 'application_has_site.site_id' );
      if( !$modifier->has_where( 'application_has_site.application_id' ) )
        $modifier->where( 'application_has_site.application_id', '=', $db_application->id );
    }
  }
}
