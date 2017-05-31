<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\vacancy;
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
    $date_string = sprintf( 'DATE( CONVERT_TZ( datetime, "UTC", "%s" ) )', $db_user->timezone );
    $this->lower_date = array( 'null' => false, 'column' => $date_string );
    $this->upper_date = array( 'null' => false, 'column' => $date_string );
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
