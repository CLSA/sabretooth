<?php
/**
 * demographics_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Participant status report data.
 * 
 * @abstract
 */
class demographics_report extends \cenozo\ui\pull\base_report
{
  /**
   * Constructor
   * 
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'demographics', $args );
  }

  /**
   * Builds the report.
   * @author Dean Inglis <inglisd@mcmaster.ca>
   * @access protected
   */
  protected function build()
  {
    $participant_class_name = lib::get_class_name( 'database\participant' );

    // get the report arguments
    $db_qnaire = lib::create( 'database\qnaire', $this->get_argument( 'restrict_qnaire_id' ) );
    $restrict_source_id = $this->get_argument( 'restrict_source_id' );
    $consent_event = $this->get_argument( 'restrict_consent_type' );
    $province_id = $this->get_argument( 'restrict_province_id' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );

    $participant_mod = lib::create( 'database\modifier' );
    if( 0 < $restrict_source_id ) $participant_mod->where( 'source_id', '=', $restrict_source_id );

    $contents = array();

    // loop through participants searching for those who have completed their most recent interview
    $participant_mod = lib::create( 'database\modifier' );
    if( $restrict_site_id )
      $participant_mod->where( 'participant_site.site_id', '=', $restrict_site_id );
    foreach( $participant_class_name::select( $participant_mod ) as $db_participant )
    {
      $db_consent = $db_participant->get_last_consent();
      if( is_null( $db_consent ) && $consent_event != 'Any' ) continue;

      $db_address = $db_participant->get_primary_address();
      if( is_null( $db_address ) ) continue;

      $region_id = $db_address->get_region()->id;
      $region_name = $db_address->get_region()->name;

      if( ( 'deceased' == $db_participant->status ) ||   
          ( $province_id && $province_id != $region_id ) ||
          ( $consent_event != 'Any' && $consent_event != $db_consent->event ) ) continue;

      $interview_mod = lib::create( 'database\modifier' );
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id ); 
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
      
      if( $db_interview && $db_interview->completed )
      {
        $mastodon_manager = lib::create( 'business\cenozo_manager', MASTODON_URL );
        $participant_obj = $mastodon_manager->pull( 'participant', 'primary', 
          array( 'uid' => $db_participant->uid ) );
       
        $alternate_list = $mastodon_manager->pull( 'participant','list_alternate',
          array( 'uid' => $db_participant->uid ) );

        $proxy = 0;
        $age = 'TBD';
        $gender = 'TBD';

        if( !is_null( $participant_obj ) && $participant_obj->success == true )
        {          
          $gender = $participant_obj->data->gender;
          $interval = util::get_interval( $participant_obj->data->date_of_birth );
          $age = $interval->format('%y');
        }

        if( !is_null( $alternate_list ) && $alternate_list->success == true )
        {
          foreach( $alternate_list->data as $alternate )
          {
            // mastodon returns values as strings
            if( $alternate->proxy == '1' )
            {
              $proxy = 1;
              break;
            }
          }
        }
       
        $contents[] = array(
          $db_participant->uid,
          $region_name,
          $gender,
          $age,
          $proxy );
      }
    }
    
    $header = array(
      "UID",
      "Prov",
      "Gender",
      "Age",
      "Proxy" );

    $this->add_table( NULL, $header, $contents, NULL );
  }
}
?>
