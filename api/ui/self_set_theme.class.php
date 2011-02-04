<?php
/**
 * self_set_theme.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * self.set_theme action
 *
 * Changes the current user's theme.
 * Arguments must include 'theme'.
 * @package sabretooth\ui
 */
class self_set_theme extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @throws exception\argument
   * @access public
   */
  public function __construct( $args = NULL )
  {
    parent::__construct( 'self', 'set_theme', $args );
    
    // grab expected arguments
    if( is_array( $args ) && array_key_exists( 'theme', $args ) )
      $this->theme_name = $args['theme'];
    
    // make sure we have all the arguments necessary
    if( !isset( $this->theme_name ) )
      throw new \sabretooth\exception\argument( 'theme_name' );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $session = \sabretooth\session::self();
    $session->get_user()->theme = $this->theme_name;
    $session->get_user()->save();
  }

  /**
   * The name of the theme to set.
   * @var string
   * @access protected
   */
  protected $theme_name = NULL;
}
?>
