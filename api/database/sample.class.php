<?php
/**
 * sample.class.php
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
 * sample: record
 *
 * @package sabretooth\database
 */
class sample extends record 
{
  /**
   * Overrides the parent class to prevent participants from being in more than one active sample
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
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
    
    // see if we are assigning a qnaire to the sample
    if( $this->qnaire_id )
    {
      $db_current = new sample( $this->id );
      if( is_null( $db_current->qnaire_id ) )
      {
        // see if this sample contains a participant who is already in an active sample
        $modifier = new modifier();
        $modifier->where( 'sample_id', '=', $this->id );
        $sub_sql = sprintf( 'SELECT participant_id FROM sample_has_participant %s',
                            $modifier->get_where() );

        $modifier = new modifier();
        $modifier->where( static::get_primary_key_name(), '!=', $this->id );
        $modifier->where( 'qnaire_id', '!=', NULL );
        $modifier->where( 'sample_has_participant.participant_id', 'IN', $sub_sql, false );
        $duplicates = sample::count( $modifier );
        if( $duplicates )
        { // a participant in the sample is already part of another sample which is active
          throw new exc\runtime(
            sprintf( 'Tried to assign a questionnaire to a sample which has %d participants who '.
                     'are already in active samples.',
                     $duplicates ), __METHOD__ );
        }
      }
    }
    
    parent::save();
  }

  /**
   * Overrides the parent class to prevent changes to the participant list if the sample is active.
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
      log::warning( 'Tried to query sample with no id.' );
      return;
    }
    
    if( 'participant' == $record_type && !is_null( $this->qnaire_id ) )
    {
      throw new exc\runtime(
        'Tried to add new participants to an active sample.', __METHOD__ );
    }

    parent::add_records( $record_type, $ids );
  }

  /**
   * Overrides the parent class to prevent changes to the participant list if the sample is active.
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
      log::warning( 'Tried to query sample with no id.' );
      return;
    }
    
    if( 'participant' == $record_type && !is_null( $this->qnaire_id ) )
    {
      throw new exc\runtime(
        'Tried to remove participants from an active sample.', __METHOD__ );
    }

    parent::remove_records( $record_type, $id );
  }
}
?>
