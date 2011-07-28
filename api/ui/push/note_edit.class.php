<?php
/**
 * note_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * push: note edit
 * 
 * Add a edit note to the provided category.
 * @package sabretooth\ui
 */
class note_edit extends \sabretooth\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'note', 'edit', $args );
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function finish()
  {
    // make sure there is a valid note category
    $category = $this->get_argument( 'category' );
    $category_class = '\\sabretooth\\database\\'.$category;
    $db_note = $category_class::get_note( $this->get_argument( 'id' ) );
    
    $sticky = $this->get_argument( 'sticky', NULL );
    if( !is_null( $sticky ) ) $db_note->sticky = 'true' == $sticky;
    
    $note = $this->get_argument( 'note', NULL );
    if( !is_null( $note ) ) $db_note->note = $note;

    $db_note->save();
  }
}
?>
