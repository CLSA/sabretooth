<?php
/**
 * participant_sync.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Base class for all list pull operations.
 * 
 * @abstract
 * @package sabretooth\ui
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
   * Returns a summary of the participant sync request as an associative array.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return array
   * @access public
   */
  public function finish()
  {
    $existing_count = 0;
    $new_count = 0;
    $address_count = 0;
    $phone_count = 0;
    $consent_count = 0;
    $missing_count = 0;
    
    $participant_class_name = lib::get_class_name( 'database\participant' );
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
        $address_count += count( $response->data->address_list );
        $phone_count += count( $response->data->phone_list );
        $consent_count += count( $response->data->consent_list );

        if( !is_null( $participant_class_name::get_unique_record( 'uid', $uid ) ) ) $existing_count++;
        else $new_count++;
      }
      // a runtime error is thrown when the participant is from the wrong cohort
      catch( \cenozo\exception\runtime $e )
      {
        throw lib::create( 'exception\notice',
          sprintf( 'Participant %s is from the wrong cohort.', $uid ), __METHOD__, $e );
      }
      // consider errors to be missing participants (may be missing or the wrong cohort)
      catch( \cenozo\exception\cenozo_service $e ) { $missing_count++; }
    }

    return array(
      'Valid participants in request' => count( $uid_list ),
      'Participants missing from Mastodon' => $missing_count,
      'New participants' => $new_count,
      'Existing participants (ignored)' => $existing_count,
      'Addresses' => $address_count,
      'Phone numbers' => $phone_count,
      'Consent entries' => $consent_count );
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
