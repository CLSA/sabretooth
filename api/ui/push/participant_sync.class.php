<?php
/**
 * participant_sync.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: participant sync
 *
 * Syncs participant information between Sabretooth and Mastodon
 * @package sabretooth\ui
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
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    // Mastodon will only return ~400 records back at a time, so break up the list into chunks
    $limit = 250;

    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $uid_list_string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $this->get_argument( 'uid_list' ) );
    $uid_list_string = trim( $uid_list_string );

    if( 'all' == $uid_list_string )
    {
      $offset = 0;
      do
      {
        $args = array(
          'full' => true,
          'limit' => $limit,
          'offset' => $offset,
          'restrictions' => array(
            'cohort' => array( 'compare' => 'is', 'value' => 'tracking' ),
            'sync_datetime' => array( 'compare' => 'is', 'value' => 'NULL' ) ) );
        $response = $mastodon_manager->pull( 'participant', 'list', $args );
        foreach( $response->data as $data ) $this->sync( $data );
      } while( count( $response->data ) );
    }
    else
    {
      $uid_list = array_unique( preg_split( '/\s+/', $uid_list_string ) );
      $count = count( $uid_list );
      for( $offset = 0; $offset < $count; $offset += $limit )
      {
        $uid_sub_list = array_slice( $uid_list, $offset, $limit );
        $args = array(
          'full' => true,
          'restrictions' => array(
            'cohort' => array( 'compare' => 'is', 'value' => 'tracking' ),
            'uid' => array( 'compare' => 'in', 'value' => implode( $uid_sub_list, ',' ) ) ) );
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
   * @access public
   */
  public function sync( $data )
  {
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $address_class_name = lib::get_class_name( 'database\address' );
    $source_class_name = lib::get_class_name( 'database\source' );
    $site_class_name = lib::get_class_name( 'database\site' );

    // if the participant already exists then skip
    $db_participant = $participant_class_name::get_unique_record( 'uid', $data->uid );
    if( !is_null( $db_participant ) ) continue;

    $db_participant = lib::create( 'database\participant' );

    foreach( $db_participant->get_column_names() as $column )
      if( 'id' != $column && 'site_id' != $column )
        $db_participant->$column = $data->$column;

    // set the source
    $db_source = is_null( $data->source_name )
               ? NULL
               : $source_class_name::get_unique_record( 'name', $data->source_name );
    $db_participant->source_id = is_null( $db_source ) ? NULL : $db_source->id;

    // set the site
    $db_site = is_null( $data->site_name )
               ? NULL
               : $site_class_name::get_unique_record( 'name', $data->site_name );
    $db_participant->site_id = is_null( $db_site ) ? NULL : $db_site->id;

    $db_participant->save();

    // update addresses
    foreach( $data->address_list as $address_info )
    {
      $db_address = lib::create( 'database\address' );
      $db_address->participant_id = $db_participant->id;

      foreach( $db_address->get_column_names() as $column )
        if( 'id' != $column && 'participant_id' != $column && 'region_id' != $column )
          $db_address->$column = $address_info->$column;

      $db_region = $region_class_name::get_unique_record(
        'abbreviation', $address_info->region_abbreviation );
      if( !is_null( $db_region ) )
        $db_address->region_id = $db_region->id;
      
      $db_address->save();
    }

    // update phones
    foreach( $data->phone_list as $phone_info )
    {
      $db_phone = lib::create( 'database\phone' );
      $db_phone->participant_id = $db_participant->id;

      foreach( $db_phone->get_column_names() as $column )
        if( 'id' != $column && 'participant_id' != $column && 'address_id' != $column )
          $db_phone->$column = $phone_info->$column;

      if( property_exists( $phone_info, 'address_rank' ) )
      {
        $db_address = $address_class_name::get_unique_record(
          array( 'participant_id', 'rank' ),
          array( $db_participant->id, $phone_info->address_rank ) );
        if( !is_null( $db_address ) )
          $db_phone->address_id = $db_address->id;
      }

      $db_phone->save();
    }

    // update consent
    foreach( $data->consent_list as $consent_info )
    {
      $db_consent = lib::create( 'database\consent' );
      $db_consent->participant_id = $db_participant->id;

      foreach( $db_consent->get_column_names() as $column )
        if( 'id' != $column && 'participant_id' != $column )
          $db_consent->$column = $consent_info->$column;

      $db_consent->save();
    }

    // let Mastodon know that the sync is done
    $datetime = util::get_datetime_object()->format( 'Y-m-d H:i:s' );
    $arguments = array(
      'noid' => array( 'participant.uid' => $db_participant->uid ),
      'columns' => array( 'sync_datetime' => $datetime ) );
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $mastodon_manager->push( 'participant', 'edit', $arguments );
  }
}
?>
