<?php
/**
 * contact.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * contact: record
 *
 * @package sabretooth\database
 */
class contact extends record
{
  /**
   * Overrides the parent class so manage ranks.
   * 
   * If the record has a rank which already exists it will push the current contact and all that
   * come after it down by one rank to make room for this one.
   * TODO: code smell: this method is identical to phase::save()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function save()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to save read-only record.' );
      return;
    }
    
    // see if there is already another contact at the new rank
    $modifier = new modifier();
    $modifier->where( 'id', '!=', $this->id );
    $modifier->where( 'participant_id', '=', $this->participant_id );
    $modifier->where( 'rank', '=', $this->rank );

    $result = static::select( $modifier );

    // if a record is found then there is already a contact in this slot
    if( 0 < count( $result ) )
    {
      // check to see if this contact is being moved or added to the list
      $modifier = new modifier();
      $modifier->where( 'id', '=', $this->id );
      $current_rank = static::db()->get_one(
        sprintf( 'SELECT rank FROM %s %s',
                 static::get_table_name(),
                 $modifier->get_sql() ) );
      
      if( !is_null( $current_rank ) )
      { // moving the contact, make room
        // determine if we are moving the rank forward or backward
        $forward = $current_rank < $this->rank;

        // get all contacts which are between the contact's current and new rank
        $modifier = new modifier();
        $modifier->where( 'participant_id', '=', $this->participant_id );
        $modifier->where( 'rank', $forward ? '>'  : '<' , $current_rank );
        $modifier->where( 'rank', $forward ? '<=' : '>=', $this->rank );
        $modifier->order( 'rank', !$forward );
        $records = static::select( $modifier );
        
        // temporarily set this record's rank to 0, preserving the new contact
        $new_rank = $this->rank;
        $this->rank = 0;
        parent::save();
        $this->rank = $new_rank;

        // and move each of the middle contact's rank backward by one
        foreach( $records as $record )
        {
          $record->rank = $record->rank + ( $forward ? -1 : 1 );
          $record->save();
        }
      }
      else
      { // adding the contact, make room
        // get all contacts at this rank and afterwards
        $modifier = new modifier();
        $modifier->where( 'participant_id', '=', $this->participant_id );
        $modifier->where( 'rank', '>=', $this->rank );
        $modifier->order_desc( 'rank' );
        $records = static::select( $modifier );
        
        // and move their rank forward by one to make room for the new contact
        foreach( $records as $record )
        {
          $record->rank++;
          $record->save();
        }
      }
    }

    // finish by saving this record
    parent::save();
  }

  /**
   * Overrides the parent class to manage ranks.
   * 
   * If there are other records after this one then we will fill up the gap caused by deleting this
   * contact.
   * TODO: code smell: this method is identical to phase::save()
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function delete()
  {
    // warn if we are in read-only mode
    if( $this->read_only )
    {
      log::warning( 'Tried to delete read-only record.' );
      return;
    }
    
    // delete the current record
    parent::delete();

    // now get a list of all contacts that come after this one
    $modifier = new modifier();
    $modifier->where( 'participant_id', '=', $this->participant_id );
    $modifier->where( 'rank', '>=', $this->rank );
    $modifier->order( 'rank' );
    $records = static::select( $modifier );

    // and now decrement the rank for all contacts from the list above
    foreach( $records as $record )
    {
      $record->rank--;
      $record->save();
    }
  }
}
?>
