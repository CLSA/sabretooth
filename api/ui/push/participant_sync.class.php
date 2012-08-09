<?php
/**
 * participant_sync.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant sync
 *
 * Syncs participant information between Sabretooth and Mastodon
 */
class participant_sync extends \cenozo\ui\push
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'sync', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // need to cut large participant lists into several consecutive requests
    $limit = 100;

    $cohort = lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $uid_list_string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $this->get_argument( 'uid_list' ) );
    $uid_list_string = trim( $uid_list_string );

    if( 'all' == $uid_list_string )
    {
      $offset = 0;
      do
      {
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'cohort', '=', $cohort );
        $modifier->where( 'sync_datetime', '=', NULL );
        $modifier->limit( $limit, $offset );
        $args = array(
          'full' => true,
          'modifier' => $modifier );
        $response = $mastodon_manager->pull( 'participant', 'list', $args );
        foreach( $response->data as $data ) $this->sync( $data );
        $offset += $limit;
      } while( count( $response->data ) );
    }
    else
    {
      $uid_list = array_unique( preg_split( '/\s+/', $uid_list_string ) );
      $count = count( $uid_list );
      for( $offset = 0; $offset < $count; $offset += $limit )
      {
        $uid_sub_list = array_slice( $uid_list, $offset, $limit );
        $modifier = lib::create( 'database\modifier' );
        $modifier->where( 'cohort', '=', $cohort );
        $modifier->where( 'uid', 'IN', $uid_sub_list );
        $args = array(
          'full' => true,
          'modifier' => $modifier );
        $response = $mastodon_manager->pull( 'participant', 'list', $args );
        foreach( $response->data as $data ) $this->sync( $data );
      }
    }
  }

  /**
   * Given participant data as returned from a request to Mastodon, this method creates the
   * participant and details.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param \StdClass $data
   * @access protected
   */
  protected function sync( $data )
  {
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $address_class_name = lib::get_class_name( 'database\address' );
    $source_class_name = lib::get_class_name( 'database\source' );
    $site_class_name = lib::get_class_name( 'database\site' );

    // if the participant already exists then don't sync
    $db_participant = $participant_class_name::get_unique_record( 'uid', $data->uid );
    if( !is_null( $db_participant ) ) return;

    $db_participant = lib::create( 'database\participant' );

    foreach( $db_participant->get_column_names() as $column )
      if( 'id' != $column ) $db_participant->$column = $data->$column;

    // set the source and site from unique keys
    $db_participant->source_id =
      $source_class_name::get_primary_from_unique_key( $data->source_id );
    $db_participant->site_id =
      $site_class_name::get_primary_from_unique_key( $data->site_id );

    $db_participant->save();

    // update sub-lists
    $this->sync_list( $db_participant, 'address', $data->address_list );
    $this->sync_list( $db_participant, 'phone', $data->phone_list );
    $this->sync_list( $db_participant, 'consent', $data->consent_list );
    $this->sync_list( $db_participant, 'availability', $data->availability_list );
    $this->sync_list( $db_participant, 'participant_note', $data->note_list );

    // let Mastodon know that the sync is done
    $datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    $arguments = array();
    $arguments['noid']['participant']['uid'] = $db_participant->uid;
    $arguments['columns']['sync_datetime'] = $datetime;
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'participant', 'edit', $arguments );
  }

  /**
   * Creates database records based on a list provided by Mastodon
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\participant $db_participant The participant to add the list to.
   * @param string $subject The subject of the list.
   * @param array $list
   * @access protected
   */
  protected function sync_list( $db_participant, $subject, $list )
  {
    foreach( $list as $data )
    {
      $record = lib::create( 'database\\'.$subject );
      $record->participant_id = $db_participant->id;

      foreach( $data as $column_name => $value )
      {
        if( '_id' == substr( $column_name, -3 ) )
        {
          $column_subject = substr( $column_name, 0, -3 );
          $class_name = lib::get_class_name( 'database\\'.$column_subject );
          $record->$column_name = $class_name::get_primary_from_unique_key( $value );
        }
        else $record->$column_name = $value;
      }

      $record->save();
    }
  }
}
?>
