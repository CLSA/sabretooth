<?php
/**
 * site_restricted_report.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all report widgets which may be restricted by site.
 * 
 * @package sabretooth\ui
 */
abstract class site_restricted_report extends base_report
{
  /**
   * Constructor
   * 
   * Defines all variables required by the report.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject of the report.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, $args );
    
    if( static::may_restrict() )
    {
      $this->add_parameter( 'restrict_site_id', 'enum', 'Site' );
    }
    else
    {
      $this->add_parameter( 'restrict_site_id', 'hidden' );

      // if restricted, show the site's name in the heading
      $predicate = bus\session::self()->get_site()->name;
      $this->set_heading( $this->get_heading().' for '.$predicate );
    }
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    if( static::may_restrict() )
    {
      // if this is an admin, give them a list of sites to choose from
      $sites = array( 0 => 'All sites' );
      foreach( db\site::select() as $db_site )
        $sites[$db_site->id] = $db_site->name;

      $this->set_parameter( 'restrict_site_id', key( $sites ), true, $sites );
    }
    else
    {
      $this->set_parameter( 'restrict_site_id', bus\session::self()->get_site()->id );
    }
    
    // this has to be done AFTER the remove_column() call above
    parent::finish();
  }

  /**
   * Determines whether the current user may choose which site to restrict by.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return boolean
   * @static
   * @access public
   */
  public static function may_restrict()
  {
    $role_name = bus\session::self()->get_role()->name;
    return 'administrator' == $role_name || 'technician' == $role_name;
  }
}
?>
