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

  /**
   * Determines the difference in hours between the user's timezone and the contact's timezone
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return float (NULL if it is not possible to get the time difference)
   * @access public
   */
  public function get_time_diff()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query contact with no id.' );
      return NULL;
    }
    
    // get the user's timezone differential from UTC
    $user_offset = util::get_datetime_object()->getOffset() / 3600;

    // if we have a postal code, then look up the postal code database (if it is available)
    if( !is_null( $this->postcode ) &&
        static::db()->get_one(
          'SELECT COUNT(*) '.
          'FROM INFORMATION_SCHEMA.SCHEMATA '.
          'WHERE SCHEMA_NAME = "postal_codes"' ) )
    {
      $postal_code = 6 == strlen( $this->postcode )
                   ? substr( $this->postcode, 0, 3 ).' '.substr( $this->postcode, -3 )
                   : $this->postcode;
      $postal_code = strtoupper( $postal_code );

      $sql = sprintf( 'SELECT TIME_ZONE, DAY_LIGHT_SAVINGS '.
                      'FROM postal_codes.postal_code '.
                      'WHERE POSTAL_CODE = "%s"',
                      $postal_code );
      $row = static::db()->get_row( $sql );
      if( 0 < count( $row ) )
      {
        $offset = -$row['TIME_ZONE'] + ( 'Y' == $row['DAY_LIGHT_SAVINGS'] ? 1 : 0 );
        return $offset - $user_offset;
      }
    }

    // if we get here then there is no way to get the time difference
    return NULL;
  }
}
?>
