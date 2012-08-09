<?php
/**
 * participant_sync.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Base class for all list pull operations.
 * 
 * @abstract
 */
class participant_sync extends \cenozo\ui\pull
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'participant', 'sync', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    // need to cut large participant lists into several consecutive requests
    $limit = 100;

    $existing_count = 0;
    $new_count = 0;
    $address_count = 0;
    $phone_count = 0;
    $consent_count = 0;
    $availability_count = 0;
    $note_count = 0;
    $missing_count = 0;
    
    $participant_class_name = lib::get_class_name( 'database\participant' );
    $cohort = lib::create( 'business\setting_manager' )->get_setting( 'general', 'cohort' );
    $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
    $uid_list_string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $this->get_argument( 'uid_list' ) );
    $uid_list_string = trim( $uid_list_string );
    
    if( 0 == strcasecmp( 'all', $uid_list_string ) )
    {
      $valid_count = 'N/A';
      $missing_count = 'N/A';

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
        foreach( $response->data as $data )
        {
          $address_count += count( $data->address_list );
          $phone_count += count( $data->phone_list );
          $consent_count += count( $data->consent_list );
          $availability_count += count( $data->availability_list );
          $note_count += count( $data->note_list );

          if( !is_null( $participant_class_name::get_unique_record( 'uid', $data->uid ) ) )
            $existing_count++;
          else $new_count++;
        }
        $offset += $limit;
      } while( count( $response->data ) );
    }
    else
    {
      $uid_list = array_unique( preg_split( '/\s+/', $uid_list_string ) );
      $valid_count = count( $uid_list );
      
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
        foreach( $response->data as $data )
        {
          $address_count += count( $data->address_list );
          $phone_count += count( $data->phone_list );
          $consent_count += count( $data->consent_list );
          $availability_count += count( $data->availability_list );
          $note_count += count( $data->note_list );

          if( !is_null( $participant_class_name::get_unique_record( 'uid', $data->uid ) ) )
            $existing_count++;
          else $new_count++;
        }

        $missing_count += count( $uid_sub_list ) - count( $response->data );
      }
    }

    $this->data = array(
      'Valid participants in request' => $valid_count,
      'Participants missing from Mastodon' => $missing_count,
      'New participants' => $new_count,
      'Existing participants (ignored)' => $existing_count,
      'Addresses' => $address_count,
      'Phone numbers' => $phone_count,
      'Consent entries' => $consent_count,
      'Availability entries' => $availability_count,
      'Note entries' => $note_count );
  }
  
  /**
   * Lists are always returned in JSON format.
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type() { return "json"; }
}
?>
