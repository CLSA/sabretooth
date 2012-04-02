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
    $site_class_name = lib::get_class_name( 'database\site' );
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $region_class_name = lib::get_class_name( 'database\region' );
    $address_class_name = lib::get_class_name( 'database\address' );
    $source_class_name = lib::get_class_name( 'database\source' );

    $db_site = $site_class_name::get_unique_record( 'name', 'Sherbrooke' );
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $uid_list_string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $this->get_argument( 'uid_list' ) );
    $uid_list_string = trim( $uid_list_string );
    $uid_list = array_unique( preg_split( '/\s+/', $uid_list_string ) );
    foreach( $uid_list as $uid )
    {
      $args = array( 'uid' => $uid, 'full' => true );
      try // if the participant is missing we'll get a mastodon error
      {
        $response = $mastodon_manager->pull( 'participant', 'primary', $args );
        
        // if the participant already exists then skip
        // TODO: upgrade so that this code includes existing participants as well
        $db_participant = $participant_class_name::get_unique_record( 'uid', $uid );
        if( !is_null( $db_participant ) ) continue;
        
        $db_participant = lib::create( 'database\participant' );

        foreach( $db_participant->get_column_names() as $column )
          if( 'id' != $column && 'site_id' != $column )
            $db_participant->$column = $response->data->$column;

        // set the source
        $db_source = is_null( $response->data->source_name )
                   ? NULL
                   : $source_class_name::get_unique_record( 'name', $response->data->source_name );
        $db_participant->source_id = is_null( $db_source ) ? NULL : $db_source->id;

        // make sure that all participant's whose prefered languge is french gets Sherbrooke's site
        // TODO: this custom code needs to be made more generic
        if( 'fr' == $db_participant->language ) $db_participant->site_id = $db_site->id;

        $db_participant->save();

        // update addresses
        foreach( $response->data->address_list as $address_info )
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
        foreach( $response->data->phone_list as $phone_info )
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
        foreach( $response->data->consent_list as $consent_info )
        {
          $db_consent = lib::create( 'database\consent' );
          $db_consent->participant_id = $db_participant->id;

          foreach( $db_consent->get_column_names() as $column )
            if( 'id' != $column && 'participant_id' != $column )
              $db_consent->$column = $consent_info->$column;

          $db_consent->save();
        }
      }
      // ignore all errors
      catch( \cenozo\exception\cenozo_service $e ) {}
    }
  }
}
?>
