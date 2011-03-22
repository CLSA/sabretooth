<?php
/**
 * participant.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * participant: record
 *
 * @package sabretooth\database
 */
class participant extends record
{
  /**
   * Overrides the parent class to prevent the participant from being added to an active sample.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int|array(int) $ids A single or array of primary key values for the record(s) being
   *                       added.
   * @throws exception\runtime
   * @access protected
   */
  protected function add_records( $record_type, $ids )
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $removed = 0;
    if( 'sample' == $record_type )
    {
      foreach( $ids as $index => $id )
      {
        // remove ids of active samples
        $db_sample = new \sabretooth\database\sample( $id );
        if( !is_null( $db_sample->qnaire_id ) )
        {
          unset( $ids[$index] );
          $removed++;
        }
      }
    }
    
    // add whichever ids are left
    if( 0 < count( $ids ) ) parent::add_records( $record_type, $ids );
    
    // report if any were not added
    if( 0 < $removed )
    {
      throw new \sabretooth\exception\runtime(
        sprintf( 'Tried to add participant to %s active sample%s.',
                 1 == $removed ? 'an' : $removed,
                 1 == $removed ? '' : 's' ),
        __METHOD__ );
    }
  }

  /**
   * Overrides the parent class to prevent the participant from being removed from an active sample.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $record_type The type of record.
   * @param int $id The primary key value for the record being removed.
   * @throws exception\runtime
   * @access protected
   */
  protected function remove_records( $record_type, $id )
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $removed = 0;
    if( 'sample' == $record_type )
    {
      foreach( $ids as $index => $id )
      {
        // remove ids of active samples
        $db_sample = new \sabretooth\database\sample( $id );
        if( !is_null( $db_sample->qnaire_id ) )
        {
          unset( $ids[$index] );
          $removed++;
        }
      }
    }
    
    // remove whichever ids are left
    if( 0 < count( $ids ) ) parent::remove_records( $record_type, $ids );
    
    // report if any were not added
    if( 0 < $removed )
    {
      throw new \sabretooth\exception\runtime(
        sprintf( 'Tried to remove participant from %s active sample%s.',
                 1 == $removed ? 'an' : $removed,
                 1 == $removed ? '' : 's' ),
        __METHOD__ );
    }
  }
}
?>
