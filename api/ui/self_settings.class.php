<?php
/**
 * self_settings.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * widget self settings
 * 
 * @package sabretooth\ui
 */
class self_settings extends widget
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'self', 'settings', $args );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();

    $session = \sabretooth\session::self();
    // create and setup the widget
    $db_user = $session->get_user();
    $db_site = $session->get_site();
    $db_role = $session->get_role();
    
    $site_names = array();
    $sites = $db_user->get_site_list();
    foreach( $sites as $site ) $site_names[] = $site->name;

    $role_names = array();
    $modifier = new \sabretooth\database\modifier();
    $modifier->where( 'site_id', '=', $db_site->id );
    $roles = $db_user->get_role_list( $modifier );
    foreach( $roles as $role ) $role_names[] = $role->name;
    
    // themes are found in the jquery-ui 
    $themes = array();
    foreach( new \DirectoryIterator( JQUERY_UI_THEMES_PATH ) as $file )
      if( !$file->isDot() && $file->isDir() ) $themes[] = $file->getFilename();

    $this->set_variable( 'user_name', $db_user->name );
    $this->set_variable( 'current_site_name', $db_site->name );
    $this->set_variable( 'current_role_name', $db_role->name );
    $this->set_variable( 'current_theme_name', $session->get_theme() );
    $this->set_variable( 'roles', $role_names );
    $this->set_variable( 'sites', $site_names );
    $this->set_variable( 'themes', $themes );
  }
}
?>
