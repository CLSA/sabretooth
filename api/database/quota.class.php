<?php
/**
 * quota.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * quota: record
 */
class quota extends \cenozo\database\quota
{
  /**
   * Extend parent method by restricting selection to records belonging to this service only
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @access public
   * @static
   */
  public static function select( $modifier = NULL, $count = false )
  {
    // make sure to only include quotas belonging to this application
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'site.service_id', '=',
                      lib::create( 'business\session' )->get_service()->id );
    return parent::select( $modifier, $count );
  }
}
