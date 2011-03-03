<?php
/**
 * phase.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * phase: active record
 *
 * @package sabretooth\database
 */
class phase extends active_record
{
  /**
   * Overrides the parent class so manage stages.
   * 
   * If the record has a stage which already exists it will push the current phase and all that
   * come after it down by one stage to make room for this one.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function save()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      \sabretooth\log::warning( 'Tried to save read-only record.' );
      return;
    }
    
    // make room for this phase if necessary
    $duplicate_id = static::get_one(
      sprintf( 'SELECT id FROM %s WHERE qnaire_id = %d AND stage = %d',
               static::get_table_name(),
               $this->qnaire_id,
               $this->stage ) );

    if( !is_null( $duplicate_id ) )
    {
      // advance it's stage by one
      $record = new static( $duplicate_id );
      $record->stage++;
      // this next line makes this method recursive, which is the desired functionality
      $record->save();
    }

    // and now save the current record
    parent::save();
  }

  /**
   * Overrides the parent class to manage stages.
   * 
   * If there are other records after this one then we will fill up the gap caused by deleting this
   * phase.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function delete()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      \sabretooth\log::warning( 'Tried to delete read-only record.' );
      return;
    }
    
    // delete the current record
    parent::delete();

    // now get a list of all phases that come after this one
    $id_list = static::get_col(
      sprintf( 'SELECT id FROM %s WHERE qnaire_id = %d AND stage >= %d ORDER BY stage',
               static::get_table_name(),
               $this->qnaire_id,
               $this->stage ) );
    
    // and now decrement the stage for all phases from the list above
    foreach( $id_list as $id )
    {
      $record = new static( $id );
      $record->stage--;
      $record->save();
    }
  }
}
?>
