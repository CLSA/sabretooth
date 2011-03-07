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
    
    // see if there is already another phase at the new stage
    $duplicate_id = static::get_one(
      sprintf( 'SELECT id FROM %s WHERE id != %d AND qnaire_id = %d AND stage = %d',
               static::get_table_name(),
               $this->id,
               $this->qnaire_id,
               $this->stage ) );

    // if duplicate_id is not null then there is already a phase in this slot
    if( !is_null( $duplicate_id ) )
    {
      // check to see if this phase is being moved or added to the list
      $current_stage = static::get_one(
        sprintf( 'SELECT stage FROM %s WHERE %s = %d',
                 static::get_table_name(),
                 static::get_primary_key_name(),
                 $this->id ) );
      
      if( !is_null( $current_stage ) )
      { // moving the phase, make room
        // determine if we are moving the stage forward or backward
        $forward = $current_stage < $this->stage;

        // get all phases which are between the phase's current and new stage
        $id_list = static::get_col(
          sprintf( 'SELECT id FROM %s '.
                   'WHERE qnaire_id = %d AND stage %s %d AND stage %s %d ORDER BY stage %s',
                   static::get_table_name(),
                   $this->qnaire_id,
                   $forward ? '>' : '<',
                   $current_stage,
                   $forward ? '<=' : '>=',
                   $this->stage,
                   $forward ? '' : 'DESC' ) );
        
        // temporarily set this record's stage to 0, preserving the new phase
        $new_stage = $this->stage;
        $this->stage = 0;
        parent::save();
        $this->stage = $new_stage;

        // and move each of the middle phase's stage backward by one
        foreach( $id_list as $id )
        {
          $record = new static( $id );
          $record->stage = $record->stage + ( $forward ? -1 : 1 );
          $record->save();
        }
      }
      else
      { // adding the phase, make room
        // get all phases at this stage and afterwards
        $id_list = static::get_col(
          sprintf( 'SELECT id FROM %s '.
                   'WHERE qnaire_id = %d AND stage >= %d ORDER BY stage DESC',
                   static::get_table_name(),
                   $this->qnaire_id,
                   $this->stage ) );
        
        // and move their stage forward by one to make room for the new phase
        foreach( $id_list as $id )
        {
          $record = new static( $id );
          $record->stage++;
          $record->save();
        }
      }
    }

    // finish by saving this record
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

  /**
   * Returns this phase's survey.
   * This overrides the parent's magic method because the survey record is outside the main db.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return surveys
   * @access public
   */
  public function get_survey()
  {
    // check the primary key value
    $primary_key_name = static::get_primary_key_name();
    if( is_null( $this->$primary_key_name ) )
    {
      \sabretooth\log::warning( 'Tried to delete record with no id.' );
      return;
    }
    
    return new limesurvey\surveys( $this->sid );
  }
}
?>
