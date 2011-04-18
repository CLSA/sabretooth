<?php
/**
 * site_restricted_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

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
   * Defines all variables required by the appointment list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject being listed.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, $args );
    
    $session = \sabretooth\business\session::self();

    // if this list has a parent don't allow restricting (the parent already does)
    if( !is_null( $this->parent ) ) {}
    else if( 'administrator' == $session->get_role()->name )
    { // the administrator may restrict by any site
      $restrict_site_id = $this->get_argument( "restrict_site_id", 0 );
      $this->db_restrict_site = $restrict_site_id
                              ? new \sabretooth\database\site( $restrict_site_id )
                              : NULL;
    }
    else // anyone else is restricted to their own site
    {
      $this->db_restrict_site = $session->get_site();
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
    parent::finish();

    $session = \sabretooth\business\session::self();
    
    // if this is an admin, give them a list of sites to choose from
    // (for lists with no parent only!)
    if( is_null( $this->parent ) && 'administrator' == $session->get_role()->name )
    {
      $sites = array();
      foreach( \sabretooth\database\site::select() as $db_site )
        $sites[$db_site->id] = $db_site->name;
      $this->set_variable( 'sites', $sites );
    }

    $this->set_variable( 'restrict_site_id',
      is_null( $this->db_restrict_site ) ? 0 : $this->db_restrict_site->id );
  }

  /**
   * The site to restrict to (for all but administrators this is automatically set to the current
   * site).
   * @var database\site
   * @access private
   */
  protected $db_restrict_site = NULL;
}
?>
