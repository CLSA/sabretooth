<?php
/**
 * shortcuts.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 */

namespace sabretooth\ui;

/**
 * shortcuts widget
 * 
 * @package sabretooth\ui
 */
class shortcuts extends widget
{
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
    $this->set_variable( 'prev_enabled', $session->slot_has_prev( 'main' ) );
    $this->set_variable( 'next_enabled', $session->slot_has_next( 'main' ) );
    $this->set_variable( 'home_enabled', 'home' != $session->slot_current( 'main' ) );
  }
}
?>
