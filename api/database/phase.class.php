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
    
    $record = NULL;
    $update_record = false;
    if( !is_null( $duplicate_id ) )
    {
      $current_stage = static::get_one(
        sprintf( 'SELECT stage FROM %s WHERE %s = %d',
                 static::get_table_name(),
                 static::get_primary_key_name(),
                 $this->id ) );

      $record = new static( $duplicate_id );
      if( $current_stage == $this->stage )
      { // the stage isn't changing, do nothing
      }
      else if( $current_stage )
      { // exchange the stage
        $record->stage = 0; // temporary value
        $record->save();
        $record->stage = $current_stage;
        $update_record = true;
      }
      else
      { // advance it's stage by one
        $record->stage++;
        // this next line may make this method recursive, which is the desired functionality
        $record->save();
      }
    }

    // and now save the current record
    parent::save();

    // and finish, if necessary
    if( $update_record && !is_null( $record ) ) $record->save();
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
