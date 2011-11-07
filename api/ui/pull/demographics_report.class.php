<?php
/**
 * demographics_report.class.php
 * 
 * @author Dean Inglis <inglisd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Participant status report data.
 * 
 * @abstract
 * @package sabretooth\ui
 */
class demographics_report extends base_report
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

  public function finish()
  {
    // get the report arguments
    $db_qnaire = new db\qnaire( $this->get_argument( 'restrict_qnaire_id' ) );
    $consent_status = $this->get_argument( 'restrict_consent_id' );
    $province_id = $this->get_argument( 'restrict_province_id' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    $participant_list = db\participant::select();
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $participant_list = db\participant::select_for_site( $db_site );
    }

    $contents = array();
    foreach( $participant_list as $db_participant )
    {
      $db_consent = $db_participant->get_last_consent();
      if( is_null( $db_consent ) && $consent_status != 'Any' ) continue;
      
      $region_id = $db_participant->get_primary_address()->get_region()->id;
      $region_name = $db_participant->get_primary_address()->get_region()->name;

      if( ( 'deceased' == $db_participant->status ) ||   
          ( $province_id && $province_id != $region_id ) ||
          ( $consent_status != 'Any' && $consent_status != $db_consent->event ) ) continue;

      $interview_mod = new db\modifier();
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id ); 
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );
      
      if( $db_interview && $db_interview->completed )
      {
        $mastodon_manager = bus\mastodon_manager::self();
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

    return parent::finish();
  }// end finish
}// end class def
?>
