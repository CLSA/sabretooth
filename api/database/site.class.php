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
}

site::add_extending_table( 'voip' );
