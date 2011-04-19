<?php
/**
 * user_add_access.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget user add_access
 * 
 * @package sabretooth\ui
 */
class user_add_access extends base_add_access
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $name The name of the operation.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'user', $args );
    
    $session = \sabretooth\business\session::self();
    if( 'administrator' == $session->get_role()->name )
    {
      // This widget is special.  We need a list of sites and roles, not an access list, so we
      // override the construction of the list_widget performed by base_add_list's constructor.
      $this->list_widget = new site_list( $args );
      $this->list_widget->set_parent( $this, 'edit' );
      $this->list_widget->set_heading( 'Choose sites to grant access to the user' );
    }
    else // not an administrator
    {
      // we need to do a bit of code bending here
      // supervisors need to be able to add access to their site, but they do not have permission
      // to view the site list - however, they don't need a site list, any access HAS to be added
      // to their site only - but, the current design of the list ui classes insists on some kind
      // of list.  So, we'll create the user list, catch the permission error, then mark the
      // template to not display a user list but instead just use the supervisor's current site
      $this->set_variable( 'force_child_id', $session->get_site()->id );
    }
  }

  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return int
   * @access protected
   */
  public function determine_site_count( $modifier = NULL )
  {
    // we want to display all sites
    return \sabretooth\database\site::count( $modifier );
  }

  /**
   * Overrides the site list widget's method.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\modifier $modifier Modifications to the list.
   * @return array( record )
   * @access protected
   */
  public function determine_site_list( $modifier = NULL )
  {
    // we want to display all sites
    return \sabretooth\database\site::select( $modifier );
  }
}
?>
