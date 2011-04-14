<?php
/**
 * note_delete.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action note delete
 * 
 * Add a delete note to the provided category.
 * @package sabretooth\ui
 */
class note_delete extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'note', 'delete', $args );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function execute()
  {
    // make sure there is a valid note category
    $category = $this->get_argument( 'category' );
    $id = $this->get_argument( 'id' );
    $note_class = '\\sabretooth\\database\\'.$category.'_note';
    $db_note = new $note_class( $id );
    $db_note->delete();
  }
}
?>
