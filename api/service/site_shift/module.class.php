<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\site_shift;
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

    if( !is_null( $min_date ) )
      $modifier->where( sprintf( 'IFNULL( end_date, "%s" )', $min_date ), '>=', $min_date );
    if( !is_null( $max_date ) )
      $modifier->where( 'start_date', '<=', $max_date );

    // only show shift templates for the user's current site
    $modifier->where( 'site_id', '=', lib::create( 'business\session' )->get_site()->id );

    if( $select->has_column( 'week' ) )
    {
      // add week column in a sub-table (so that counts work when restricting by this column)
      $sub_sel = lib::create( 'database\select' );
      $sub_sel->from( 'site_shift' );
      $sub_sel->add_column( 'id' );
      $sub_sel->add_column(
        'IF( "weekly" = repeat_type, '.
            'CONCAT( IF( monday, "M", "_" ), '.
                    'IF( tuesday, "T", "_" ), '.
                    'IF( wednesday, "W", "_" ), '.
                    'IF( thursday, "T", "_" ), '.
                    'IF( friday, "F", "_" ), '.
                    'IF( saturday, "S", "_" ), '.
                    'IF( sunday, "S", "_" ) ), '.
            '"(n/a)" )',
        'week',
        false );

      $modifier->join(
        sprintf( '( %s ) AS site_shift_week', $sub_sel->get_sql() ),
        'site_shift.id',
        'site_shift_week.id' );
      $select->add_column( 'site_shift_week.week', 'week', false );
    }
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
