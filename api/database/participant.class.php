<?php
/**
 * participant.class.php
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
 * participant: record
 *
 * @package sabretooth\database
 */
class participant extends has_note
{
  /**
   * Identical to the parent's select method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the selection to.
   * @param modifier $modifier Modifications to the selection.
   * @param boolean $count If true the total number of records instead of a list
   * @return array( record ) | int
   * @static
   * @access public
   */
  public static function select_for_site( $db_site, $modifier = NULL, $count = false )
  {
    // if there is no site restriction then just use the parent method
    if( is_null( $db_site ) ) return parent::select( $modifier, $count );

    $select_tables = 'participant, participant_primary_location, contact';

    // straight join the tables
    if( is_null( $modifier ) ) $modifier = new modifier();
    $modifier->where( 'participant.id', '=', 'participant_primary_location.participant_id', false );
    $modifier->where( 'participant_primary_location.contact_id', '=', 'contact.id', false );

    $sql = sprintf( ( $count ? 'SELECT COUNT( %s.%s ) ' : 'SELECT %s.%s ' ).
                    'FROM %s '.
                    'WHERE ( participant.site_id = %d '.
                    '  OR contact.province_id IN '.
                    '  ( SELECT id FROM province WHERE site_id = %d ) ) %s',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    $select_tables,
                    $db_site->id,
                    $db_site->id,
                    $modifier->get_sql( true ) );

    if( $count )
    {
      return intval( static::db()->get_one( $sql ) );
    }
    else
    {
      $id_list = static::db()->get_col( $sql );
      $records = array();
      foreach( $id_list as $id ) $records[] = new static( $id );
      return $records;
    }
  }

  /**
   * Identical to the parent's count method but restrict to a particular site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param site $db_site The site to restrict the count to.
   * @param modifier $modifier Modifications to the count.
   * @return int
   * @static
   * @access public
   */
  public static function count_for_site( $db_site, $modifier = NULL )
  {
    return static::select_for_site( $db_site, $modifier, true );
  }
  
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
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $removed = 0;
    if( 'sample' == $record_type )
    {
      foreach( $ids as $index => $id )
      {
        // remove ids of active samples
        $db_sample = new sample( $id );
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
      throw new exc\runtime(
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
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $removed = 0;
    if( 'sample' == $record_type )
    {
      foreach( $ids as $index => $id )
      {
        // remove ids of active samples
        $db_sample = new sample( $id );
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
      throw new exc\runtime(
        sprintf( 'Tried to remove participant from %s active sample%s.',
                 1 == $removed ? 'an' : $removed,
                 1 == $removed ? '' : 's' ),
        __METHOD__ );
    }
  }
  
  /**
   * Returns the currently active sample that the participant belongs to, or NULL if the
   * participant does not belong to an active sample (ie: is not queued for any interviews)
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return sample
   * @access public
   */
  public function get_active_sample()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $modifier = new modifier();
    $modifier->where( 'qnaire_id', '!=', NULL );
    $modifier->where( 'sample_has_participant.sample_id', '=', 'sample.id', false );
    $modifier->where( 'sample_has_participant.participant_id', '=', $this->id );
    $sample_list = sample::select( $modifier );

    // warn if there are more than one active samples (this should never happen)
    if( 1 < count( $sample_list ) )
      log::crit(
        sprintf( 'Participant %d belongs to more than one active sample!',
                  $this->id ) );

    return 0 < count( $sample_list ) ? current( $sample_list ) : NULL;
  }

  /**
   * Get the participants last (non active) assignment
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $assignment_id = static::db()->get_one(
      sprintf( 'SELECT assignment_id '.
               'FROM participant_last_assignment '.
               'WHERE participant_id = %s',
               database::format_string( $this->id ) ) );
    return $assignment_id ? new assignment( $assignment_id ) : NULL;
  }

  /**
   * Get the participants last (non active) assignment
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return assignment
   * @access public
   */
  public function get_last_finished_assignment()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $assignment_id = static::db()->get_one(
      sprintf( 'SELECT assignment_id '.
               'FROM participant_last_finished_assignment '.
               'WHERE participant_id = %s',
               database::format_string( $this->id ) ) );
    return $assignment_id ? new assignment( $assignment_id ) : NULL;
  }

  /**
   * Get the participant's current defining consent
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return consent
   * @access public
   */
  public function get_current_consent()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $consent_id = static::db()->get_one(
      sprintf( 'SELECT consent_id FROM participant_current_consent WHERE participant_id = %s',
               database::format_string( $this->id ) ) );
    return $consent_id ? new consent( $consent_id ) : NULL;
  }

  /**
   * Get the participant's primary location
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return contact
   * @access public
   */
  public function get_primary_location()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $contact_id = static::db()->get_one(
      sprintf( 'SELECT contact_id FROM participant_primary_location WHERE participant_id = %s',
               database::format_string( $this->id ) ) );
    return $contact_id ? new contact( $contact_id ) : NULL;
  }
}
?>
