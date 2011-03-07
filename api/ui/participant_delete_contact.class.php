<?php
/**
 * participant_delete_contact.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action participant delete_contact
 * 
 * @package sabretooth\ui
 */
class participant_delete_contact extends base_delete_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'contact', $args );
  }
}
?>
