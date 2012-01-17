<?php
/**
 * participant.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * participant: record
 *
 * @package sabretooth\database
 */
class participant extends \cenozo\database\has_note
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

    // left join the participant_primary_address and address tables
    if( is_null( $modifier ) ) $modifier = lib::create( 'database\modifier' );
    $sql = sprintf( ( $count ? 'SELECT COUNT( %s.%s ) ' : 'SELECT %s.%s ' ).
                    'FROM %s '.
                    'LEFT JOIN participant_primary_address '.
                    'ON %s.id = participant_primary_address.participant_id '.
                    'LEFT JOIN address '.
                    'ON participant_primary_address.address_id = address.id '.
                    'WHERE ( %s.site_id = %d '.
                    '  OR ( %s.site_id IS NULL '.
                    '    AND address.region_id IN ( '.
                    '      SELECT id FROM region WHERE site_id = %d ) ) ) %s',
                    static::get_table_name(),
                    static::get_primary_key_name(),
                    static::get_table_name(),
                    static::get_table_name(),
                    static::get_table_name(),
                    $db_site->id,
                    static::get_table_name(),
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
   * Get the participant's most recent assignment.
   * This will return the participant's current assignment, or the most recently closed assignment
   * if the participant is not currently assigned.
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
    $class_name = lib::get_class_name( 'database\database' );
    $assignment_id = static::db()->get_one(
      sprintf( 'SELECT assignment_id '.
               'FROM participant_last_assignment '.
               'WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $assignment_id ? lib::create( 'database\assignment', $assignment_id ) : NULL;
  }

  /**
   * Get the participant's most recent, closed assignment.
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
    
    $modifier = lib::create( 'database\modifier' );
    $modifier->where( 'interview.participant_id', '=', $this->id );
    $modifier->where( 'end_datetime', '!=', NULL );
    $modifier->order_desc( 'start_datetime' );
    $modifier->limit( 1 );
    $class_name = lib::get_class_name( 'database\assignment' );
    $assignment_list = $class_name::select( $modifier );

    return 0 == count( $assignment_list ) ? NULL : current( $assignment_list );
  }

  /**
   * Get the participant's last consent
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return consent
   * @access public
   */
  public function get_last_consent()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $consent_id = static::db()->get_one(
      sprintf( 'SELECT consent_id '.
               'FROM participant_last_consent '.
               'WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $consent_id ? lib::create( 'database\consent', $consent_id ) : NULL;
  }

  /**
   * Get the participant's "primary" address.  This is the highest ranking canadian address.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_primary_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_primary_address WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the participant's "first" address.  This is the highest ranking, active, available
   * address.
   * Note: this address may be in the United States
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return address
   * @access public
   */
  public function get_first_address()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $address_id = static::db()->get_one(
      sprintf( 'SELECT address_id FROM participant_first_address WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $address_id ? lib::create( 'database\address', $address_id ) : NULL;
  }

  /**
   * Get the site that the participant belongs to.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return site
   * @access public
   */
  public function get_primary_site()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    $db_site = NULL;

    if( !is_null( $this->site_id ) )
    { // site is specifically defined
      $db_site = $this->get_site();
    }
    else
    {
      $db_address = $this->get_primary_address();
      if( !is_null( $db_address ) )
      { // there is a primary address
        $db_site = $db_address->get_region()->get_site();
      }
    }

    return $db_site;
  }
  
  /**
   * Get the last phone call which reached the participant
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return phone_call
   * @access public
   */
  public function get_last_contacted_phone_call()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    // need custom SQL
    $class_name = lib::get_class_name( 'database\database' );
    $phone_call_id = static::db()->get_one(
      sprintf( 'SELECT phone_call_id FROM participant_last_contacted_phone_call WHERE participant_id = %s',
               $class_name::format_string( $this->id ) ) );
    return $phone_call_id ? lib::create( 'database\phone_call', $phone_call_id ) : NULL;
  }

  /**
   * Override parent's magic get method so that supplementary data can be retrieved
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $column_name The name of the column or table being fetched from the database
   * @return mixed
   * @access public
   */
  public function __get( $column_name )
  {
    if( 'current_qnaire_id' == $column_name || 'start_qnaire_date' == $column_name )
    {
      $this->get_queue_data();
      return $this->$column_name;
    }

    return parent::__get( $column_name );
  }

  /**
   * Fills in the current qnaire id and start qnaire date
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access private
   */
  private function get_queue_data()
  {
    // check the primary key value
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to query participant with no id.' );
      return NULL;
    }
    
    if( is_null( $this->current_qnaire_id ) && is_null( $this->start_qnaire_date ) )
    {
      $class_name = lib::get_class_name( 'database\database' );
      $sql = sprintf( 'SELECT current_qnaire_id, start_qnaire_date '.
                      'FROM participant_for_queue '.
                      'WHERE id = %s',
                      $class_name::format_string( $this->id ) );
      $row = static::db()->get_row( $sql );
      $this->current_qnaire_id = $row['current_qnaire_id'];
      $this->start_qnaire_date = $row['start_qnaire_date'];
    }
  }

  /**
   * The participant's current questionnaire id (from participant_for_queue)
   * @var int
   * @access private
   */
  private $current_qnaire_id = NULL;

  /**
   * The date that the current questionnaire is to begin (from participant_for_queue)
   * @var int
   * @access private
   */
  private $start_qnaire_date = NULL;
}
?>
