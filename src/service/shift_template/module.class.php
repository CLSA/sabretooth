<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\shift_template;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\base_calendar_module
{
  /**
   * Contructor
   */
  public function __construct( $index, $service )
  {
    parent::__construct( $index, $service );
    // lower-end_date and upper-start_date are purposefully backward
    $this->lower_date = array( 'null' => false, 'column' => 'end_date' );
    $this->upper_date = array( 'null' => true, 'column' => 'start_date' );
  }

  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    $record = $this->get_resource();
    if( !is_null( $record ) )
    {
      // restrict by site
      $db_restrict_site = $this->get_restricted_site();
      if( !is_null( $db_restrict_site ) )
      {
        if( $record->site_id != $db_restrict_site->id )
          $this->get_status()->set_code( 403 );
      }
    }
  }

  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    // restrict by site
    $db_restrict_site = $this->get_restricted_site();
    if( !is_null( $db_restrict_site ) ) $modifier->where( 'site_id', '=', $db_restrict_site->id );

    if( $select->has_column( 'week' ) )
    {
      // add week column in a sub-table (so that counts work when restricting by this column)
      $sub_sel = lib::create( 'database\select' );
      $sub_sel->from( 'shift_template' );
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
        sprintf( '( %s ) AS shift_template_week', $sub_sel->get_sql() ),
        'shift_template.id',
        'shift_template_week.id' );
      $select->add_column( 'shift_template_week.week', 'week', false );
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
