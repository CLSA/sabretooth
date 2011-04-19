<?php
/**
 * contact_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action contact edit
 *
 * Edit a contact.
 * @package sabretooth\ui
 */
class contact_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'contact', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    $columns = $this->get_argument( 'columns' );

    // if there is a phone number, validate it
    if( $columns['phone'] )
    {
      if( 10 != strlen( preg_replace( '/[^0-9]/', '', $columns['phone'] ) ) )
        throw new \sabretooth\exception\notice(
          'Phone number must have exactly 10 digits.', __METHOD__ );
    }

    parent::execute();
  }
}
?>
