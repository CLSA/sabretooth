<?php
/**
 * has_rank.class.php
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
 * A base class for all records which have a unique, ordered rank.
 *
 * @package sabretooth\database
 */
abstract class has_rank extends record
{
  /**
   * Overrides the parent class so manage ranks.
   * 
   * If the record has a rank which already exists it will push the current record and all that
   * come after it down by one rank to make room for this one.
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
    
    $rank_parent_key = is_null( static::$rank_parent ) ? false : static::$rank_parent.'_id';

    // see if there is already another record at the new rank
    $modifier = new modifier();
    $modifier->where( 'id', '!=', $this->id );
    if( $rank_parent_key ) $modifier->where( $rank_parent_key, '=', $this->$rank_parent_key );
    $modifier->where( 'rank', '=', $this->rank );

    $result = static::select( $modifier );

    // if a record is found then there is already a record in this slot
    if( 0 < count( $result ) )
    {
      // check to see if this record is being moved or added to the list
      $modifier = new modifier();
      $modifier->where( 'id', '=', $this->id );
      $current_rank = static::db()->get_one(
        sprintf( 'SELECT rank FROM %s %s',
                 static::get_table_name(),
                 $modifier->get_sql() ) );
      
      if( !is_null( $current_rank ) )
      { // moving the record, make room
        // determine if we are moving the rank forward or backward
        $forward = $current_rank < $this->rank;

        // get all records which are between the record's current and new rank
        $modifier = new modifier();
        if( $rank_parent_key ) $modifier->where( $rank_parent_key, '=', $this->$rank_parent_key );
        $modifier->where( 'rank', $forward ? '>'  : '<' , $current_rank );
        $modifier->where( 'rank', $forward ? '<=' : '>=', $this->rank );
        $modifier->order( 'rank', !$forward );
        $records = static::select( $modifier );
        
        // temporarily set this record's rank to 0, preserving the new record
        $new_rank = $this->rank;
        $this->rank = 0;
        parent::save();
        $this->rank = $new_rank;

        // and move each of the middle record's rank backward by one
        foreach( $records as $record )
        {
          $record->rank = $record->rank + ( $forward ? -1 : 1 );
          $record->save();
        }
      }
      else
      { // adding the record, make room
        // get all records at this rank and afterwards
        $modifier = new modifier();
        if( $rank_parent_key ) $modifier->where( $rank_parent_key, '=', $this->$rank_parent_key );
        $modifier->where( 'rank', '>=', $this->rank );
        $modifier->order_desc( 'rank' );
        $records = static::select( $modifier );
        
        // and move their rank forward by one to make room for the new record
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
   * record.
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

    $rank_parent_key = is_null( static::$rank_parent ) ? false : static::$rank_parent.'_id';

    // now get a list of all records that come after this one
    $modifier = new modifier();
    if( $rank_parent_key ) $modifier->where( $rank_parent_key, '=', $this->$rank_parent_key );
    $modifier->where( 'rank', '>=', $this->rank );
    $modifier->order( 'rank' );
    $records = static::select( $modifier );

    // and now decrement the rank for all records from the list above
    foreach( $records as $record )
    {
      $record->rank--;
      $record->save();
    }
  }

  /**
   * The type of record which the record has a rank for.  Leave null if there is no parent.
   * @var string
   * @access protected
   * @static
   */
  protected static $rank_parent = NULL;
}
?>
