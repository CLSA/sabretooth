<?php
/**
 * site_restricted_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all list widgets which may be restricted by site.
 * 
 * @package sabretooth\ui
 */
abstract class site_restricted_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the site restricted list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being listed.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, $args );
    
    if( static::may_restrict() )
    {
      $restrict_site_id = $this->get_argument( "restrict_site_id", 0 );
      $this->db_restrict_site = $restrict_site_id
                              ? new db\site( $restrict_site_id )
                              : NULL;
    }
    else // anyone else is restricted to their own site
    {
      $this->db_restrict_site = bus\session::self()->get_site();
    }
    
    // if restricted, show the site's name in the heading
    $predicate = is_null( $this->db_restrict_site ) ? 'all sites' : $this->db_restrict_site->name;
    $this->set_heading( $this->get_heading().' for '.$predicate );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // if this list has a parent don't allow restricting (the parent already does)
    if( !is_null( $this->parent ) ) $this->db_restrict_site = NULL;

    if( static::may_restrict() )
    {
      // if this is an admin, give them a list of sites to choose from
      // (for lists with no parent only!)
      if( is_null( $this->parent ) )
      {
        $sites = array();
        foreach( db\site::select() as $db_site )
          $sites[$db_site->id] = $db_site->name;
        $this->set_variable( 'sites', $sites );
      }
    }

    if( is_null( $this->db_restrict_site ) )
    {
      $this->set_variable( 'restrict_site_id', 0 );
    }
    else
    { // we're restricting to the user's site, so remove the site column
      $this->remove_column( 'site.name' );
      $this->set_variable( 'restrict_site_id', $this->db_restrict_site->id );
    }
    
    // this has to be done AFTER the remove_column() call above
    parent::finish();
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  protected function determine_record_count( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
    }

    return parent::determine_record_count( $modifier );
  }

  /**
   * Overrides the parent class method based on the restrict site member.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  protected function determine_record_list( $modifier = NULL )
  {
    if( !is_null( $this->db_restrict_site ) )
    {
      if( NULL == $modifier ) $modifier = new db\modifier();
      $modifier->where( 'site_id', '=', $this->db_restrict_site->id );
    }

    return parent::determine_record_list( $modifier );
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
    return 'administrator' == $role_name;
  }

  /**
   * The site to restrict to (for all but administrators this is automatically set
   * to the current site).
   * @var database\site
   * @access private
   */
  protected $db_restrict_site = NULL;
}
?>
