<?php
/**
 * note_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget note list
 * 
 * @package sabretooth\ui
 */
class note_list extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables which need to be set for the associated template.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'note', 'list', $args );
  }

  /**
   * Finish setting the variables in a widget.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    // make sure there is a valid note category
    $category = $this->get_argument( 'category' );
    $category_id = $this->get_argument( 'category_id' );
    $category_class = '\\sabretooth\\database\\'.$category;
    $db_record = new $category_class( $category_id );
    if( !is_a( $db_record, '\\sabretooth\\database\\has_note' ) )
      throw new exc\runtime(
        sprintf( 'Tried to list notes for %s which cannot have notes.', $category ),
        __METHOD__ );
    
    // get the record's note list
    $note_list = array();
    foreach( $db_record->get_note_list() as $db_note )
    {
      $date = 7 > util::get_interval( $db_note->datetime )->days
            ? util::get_fuzzy_period_ago( $db_note->datetime )
            : util::get_formatted_date( $db_note->datetime );
      $note_list[] = array( 'id' => $db_note->id,
                            'sticky' => $db_note->sticky,
                            'user' => $db_note->get_user()->name,
                            'date' => $date,
                            'note' => $db_note->note );
    }

    $this->set_variable( 'category', $category );
    $this->set_variable( 'category_id', $category_id );
    $this->set_variable( 'note_list', $note_list );

    // allow supervisers and admins to modify notes
    $role_name = bus\session::self()->get_role()->name;
    if( 'administrator' == $role_name || 'supervisor' == $role_name )
    {
      $this->set_variable( 'stickable', true );
      $this->set_variable( 'removable', true );
      $this->set_variable( 'editable', true );
    }
    else
    {
      $this->set_variable( 'stickable', false );
      $this->set_variable( 'removable', false );
      $this->set_variable( 'editable', false );
    }
  }
}
?>
