<?php
/**
 * address.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * address: record
 *
 * @package sabretooth\database
 */
class address extends has_rank
{
  /**
   * Determines the difference in hours between the user's timezone and the address's timezone
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return float (NULL if it is not possible to get the time difference)
   * @access public
   */
  public function get_time_diff()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query address with no id.' );
      return NULL;
    }
    
    // get the user's timezone differential from UTC
    $user_offset = util::get_datetime_object()->getOffset() / 3600;

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
        $offset = $row['timezone_offset'] + ( $row['daylight_savings'] ? 1 : 0 );
        return $offset - $user_offset;
      }
    }

    // if we get here then there is no way to get the time difference
    return NULL;
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
