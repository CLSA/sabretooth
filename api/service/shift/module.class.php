<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\shift;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by date, if requested
    $min_date = $this->get_argument( 'min_date', NULL );
    $max_date = $this->get_argument( 'max_date', NULL );

    if( !is_null( $min_date ) ) $modifier->where( 'DATE( end_datetime )', '>=', $min_date );
    if( !is_null( $max_date ) ) $modifier->where( 'DATE( start_datetime )', '<=', $max_date );

    // only show shifts for the user's current site
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );

    // include the user first/last/name as supplemental data
    $modifier->join( 'user', 'shift.user_id', 'user.id' );
    $select->add_table_column( 'user', 'name', 'username' );
    $select->add_column(
      'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
      'formatted_user_id',
      false );
  }

  /**
   * Extend parent method
   */
  public function pre_write( $record )
  {
    // force the site to the current user's site
    $record->site_id = lib::create( 'business\session' )->get_site()->id;
  }
}
