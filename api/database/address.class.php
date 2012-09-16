<?php
/**
 * address.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * address: record
 */
class address extends \cenozo\database\has_rank
{
  /**
   * Sets the region, timezone offset and daylight savings columns based on the postcode.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function source_postcode()
  {
    $postcode_class_name = lib::get_class_name( 'database\postcode' );
    if( !is_null( $this->postcode ) )
    {
      $db_postcode = $postcode_class_name::get_match( $this->postcode );
      if( !is_null( $db_postcode ) )
      {
        $this->region_id = $db_postcode->region_id;
        $this->timezone_offset = $db_postcode->timezone_offset;
        $this->daylight_savings = $db_postcode->daylight_savings;
      }
    }
  }

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

    if( !is_null( $this->timezone_offset ) && !is_null( $this->daylight_savings ) )
    {
      $offset = $this->timezone_offset;
      if( $under_daylight_savings && $this->daylight_savings ) $offset += 1;
      return $offset - $user_offset;
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

    // look up the postal code for the correct region
    $postcode_class_name = lib::get_class_name( 'database\postcode' );
    $db_postcode = $postcode_class_name::get_match( $this->postcode );
    if( is_null( $db_postcode ) ) return NULL;
    return $db_postcode->region_id == $this->region_id;
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
