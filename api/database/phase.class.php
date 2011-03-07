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
    $modifier = new modifier();
    $modifier->where( 'id', '!=', $this->id );
    $modifier->where( 'qnaire_id', '=', $this->qnaire_id );
    $modifier->where( 'stage', '=', $this->stage );

    $result = static::select( $modifier );

    // if a record is found then there is already a phase in this slot
    if( 0 < count( $result ) )
    {
      // check to see if this phase is being moved or added to the list
      $modifier = new modifier();
      $modifier->where( 'id', '=', $this->id );
      $current_stage = static::get_one(
        sprintf( 'SELECT stage FROM %s %s',
                 static::get_table_name(),
                 $modifier->get_sql() ) );
      
      if( !is_null( $current_stage ) )
      { // moving the phase, make room
        // determine if we are moving the stage forward or backward
        $forward = $current_stage < $this->stage;

        // get all phases which are between the phase's current and new stage
        $modifier = new modifier();
        $modifier->where( 'qnaire_id', '=', $this->qnaire_id );
        $modifier->where( 'stage', $forward ? '>'  : '<' , $current_stage );
        $modifier->where( 'stage', $forward ? '<=' : '>=', $this->stage );
        $modifier->order( 'stage', !$forward );
        $records = static::select( $modifier );
        
        // temporarily set this record's stage to 0, preserving the new phase
        $new_stage = $this->stage;
        $this->stage = 0;
        parent::save();
        $this->stage = $new_stage;

        // and move each of the middle phase's stage backward by one
        foreach( $records as $record )
        {
          $record->stage = $record->stage + ( $forward ? -1 : 1 );
          $record->save();
        }
      }
      else
      { // adding the phase, make room
        // get all phases at this stage and afterwards
        $modifier = new modifier();
        $modifier->where( 'qnaire_id', '=', $this->qnaire_id );
        $modifier->where( 'stage', '>=', $this->stage );
        $modifier->order_desc( 'stage' );
        $records = static::select( $modifier );
        
        // and move their stage forward by one to make room for the new phase
        foreach( $records as $record )
        {
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
    $modifier = new modifier();
    $modifier->where( 'qnaire_id', '=', $this->qnaire_id );
    $modifier->where( 'stage', '>=', $this->stage );
    $modifier->order( 'stage' );
    $records = static::select( $modifier );

    // and now decrement the stage for all phases from the list above
    foreach( $records as $record )
    {
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
