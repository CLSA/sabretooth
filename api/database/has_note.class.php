<?php
/**
 * note.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * A base class for all records which have notes.
 *
 * @package sabretooth\database
 */
abstract class has_note extends record
{
  /**
   * Gets the number of notes associated with this record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier
   * @return int
   * @access public
   */
  public function get_note_count( $modifier = NULL )
  {
    $table_name = static::get_table_name();
    $subject_key_name = $table_name.'_'.static::get_primary_key_name();
    $note_class_name = '\\sabretooth\\database\\'.$table_name.'_note';

    if ( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( $subject_key_name, '=', $this->id );
    return $note_class_name::count( $modifier );
  }

  /**
   * Gets the list of notes associated with this record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param modifier $modifier
   * @return array( record )
   * @access public
   */
  public function get_note_list( $modifier = NULL )
  {
    $table_name = static::get_table_name();
    $subject_key_name = $table_name.'_'.static::get_primary_key_name();
    $note_class_name = '\\sabretooth\\database\\'.$table_name.'_note';

    if ( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( $subject_key_name, '=', $this->id );
    $modifier->order( 'sticky', true );
    $modifier->order( 'date' );
    return $note_class_name::select( $modifier );
  }

  /**
   * Adds a new note to the record.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param user $user
   * @param string $note
   * @access public
   */
  public function add_note( $user, $note )
  {
    $table_name = static::get_table_name();
    $subject_key_name = $table_name.'_'.static::get_primary_key_name();
    $note_class_name = '\\sabretooth\\database\\'.$table_name.'_note';
    $db_note = new $note_class_name();
    $db_note->user_id = $user->id;
    $db_note->$subject_key_name = $this->id;
    $db_note->date = date( 'Y-m-d H:i:s' );
    $db_note->note = $note;
    $db_note->save();
  }
}
?>
