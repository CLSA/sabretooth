<?php
/**
 * note_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action note new
 * 
 * Add a new note to the provided category.
 * @package sabretooth\ui
 */
class note_new extends action
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'note', 'new', $args );
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
    $category_id = $this->get_argument( 'category_id' );
    $note = $this->get_argument( 'note' );
    $category_class = '\\sabretooth\\database\\'.$category;
    $db_record = new $category_class( $category_id );
    if( !is_a( $db_record, '\\sabretooth\\database\\has_note' ) )
      throw new \sabretooth\exception\runtime(
        sprintf( 'Tried to create new note to %s which cannot have notes.', $category ),
        __METHOD__ );

    $db_record->add_note( \sabretooth\business\session::self()->get_user(), $note );
  }
}
?>
