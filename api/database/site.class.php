<?php
/**
 * site.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * site: record
 */
class site extends \cenozo\database\site
{
  /**
   * Gives a complete name for the site in the form of "name"
   * This method overrides the parent class to remove the (service) part of the name
   * 
   * @author Patrick Emond <emondpd@mcamster.ca>
   * @access public
   */
  public function get_full_name()
  {
    return $this->name;
  }

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
    // make sure to only include sites belonging to this application
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'service_id', '=', lib::create( 'business\session' )->get_service()->id );
    return parent::select( $modifier, $count );
  }
}

site::add_extending_table( 'voip' );
