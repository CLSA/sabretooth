<?php
/**
 * address.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * address: record
 *
 * @package sabretooth\database
 */
class address extends \cenozo\database\has_rank
{
  /**
   * Determines the difference in hours between the user's timezone and the address's timezone
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return float (NULL if it is not possible to get the time difference)
   * @access public
   */
  public function get_time_diff()
  {
    // get the user's timezone differential from UTC
    $user_offset = util::get_datetime_object()->getOffset() / 3600;

    // determine if we are currently under daylight savings
    $summer_offset = util::get_datetime_object( '2000-07-01' )->getOffset() / 3600;
    $under_daylight_savings = $user_offset == $summer_offset;

    // if we have a postal code, then look up the postal code database (if it is available)
    if( !is_null( $this->postcode ) &&
        static::db()->get_one(
          'SELECT COUNT(*) '.
          'FROM information_schema.schemata '.
          'WHERE schema_name = "address_info"' ) )
    {
      $postcode = 6 == strlen( $this->postcode )
                   ? substr( $this->postcode, 0, 3 ).' '.substr( $this->postcode, -3 )
                   : $this->postcode;
      $postcode = strtoupper( $postcode );

      $sql = sprintf( 'SELECT timezone_offset, daylight_savings '.
                      'FROM address_info.postcode '.
                      'WHERE postcode = "%s"',
                      $postcode );
      $row = static::db()->get_row( $sql );
      if( 0 < count( $row ) )
      {
        $offset = $row['timezone_offset'];
        if( $under_daylight_savings &&  $row['daylight_savings'] ) $offset += 1;
        return $offset - $user_offset;
      }
    }

    // if we get here then there is no way to get the time difference
    return NULL;
  }

  /**
   * Determines if the address is valid by making sure all address-based manditory fields
   * are filled and checking for postcode-region mismatches.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @access public
   */
  public function is_valid()
  {
    // make sure all mandatory address-based fields are filled in
    if( is_null( $this->address1 ) ||
        is_null( $this->city ) ||
        is_null( $this->region_id ) ||
        is_null( $this->postcode ) ) return false;

    // look up the postal code database (if it is available)
    if( !is_null( $this->postcode ) &&
        static::db()->get_one(
          'SELECT COUNT(*) '.
          'FROM information_schema.schemata '.
          'WHERE schema_name = "address_info"' ) )
    {
      $postcode = 6 == strlen( $this->postcode )
                   ? substr( $this->postcode, 0, 3 ).' '.substr( $this->postcode, -3 )
                   : $this->postcode;
      $postcode = strtoupper( $postcode );

      $sql = sprintf( 'SELECT st '.
                      'FROM address_info.zcu '.
                      'WHERE zip = "%s"',
                      $postcode );
      $abbreviation = static::db()->get_one( $sql );
      if( is_null( $abbreviation ) ) return false;

      // create the region record directly (since this record may not exist in the database)
      $db_region = lib::create( 'database\region', $this->region_id );

      // check for postcode/province mismatches
      if( $db_region->abbreviation != $abbreviation ) return false;
    }
    
    return true;
  }

  /**
   * The type of record which the record has a rank for.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = 'participant';
}
?>
