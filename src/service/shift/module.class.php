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
class module extends \cenozo\service\base_calendar_module
{
  /**
   * Contructor
   */
  public function __construct( $index, $service )
  {
    parent::__construct( $index, $service );
    $db_user = lib::create( 'business\session' )->get_user();
    $this->lower_date = array(
      'null' => false,
      'column' => sprintf( 'DATE( CONVERT_TZ( start_datetime, "UTC", "%s" ) )', $db_user->timezone )
    );
    $this->upper_date = array(
      'null' => false,
      'column' => sprintf( 'DATE( CONVERT_TZ( end_datetime, "UTC", "%s" ) )', $db_user->timezone )
    );
  }

  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( 300 > $this->get_status()->get_code() )
    {
      $record = $this->get_resource();
      if( !is_null( $record ) )
      {
        // restrict by site
        $db_restrict_site = $this->get_restricted_site();
        if( !is_null( $db_restrict_site ) && !is_null( $record->site_id ) )
        {
          if( $record->site_id != $db_restrict_site->id )
            $this->get_status()->set_code( 403 );
        }
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

    $modifier->join( 'user', 'shift.user_id', 'user.id' );
    $select->add_table_column( 'user', 'name', 'username' );

    if( !is_null( $this->get_resource() ) )
    {
      // include the user first/last/name as supplemental data
      $select->add_column(
        'CONCAT( user.first_name, " ", user.last_name, " (", user.name, ")" )',
        'formatted_user_id',
        false );
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
