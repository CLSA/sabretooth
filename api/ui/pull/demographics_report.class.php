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
    $db_qnaire = new db\qnaire( $this->get_argument( 'qnaire_id' ) );
    $consent_status = $this->get_argument( 'consent_type' );
    $province = $this->get_argument( 'region_type' );
    $restrict_site_id = $this->get_argument( 'restrict_site_id', 0 );
    
    $title = 'Participant Demographics Report';
    if( $restrict_site_id )
    {
      $db_site = new db\site( $restrict_site_id );
      $title = $title.' for '.$db_site->name;
    }

    $this->add_title( $title );

    $contents = array();
    
    $participant_list = $restrict_site_id
                      ? db\participant::select_for_site( $db_site )
                      : db\participant::select();
    foreach( $participant_list as $db_participant )
    {
      $db_consent = $db_participant->get_last_consent();
      if( is_null( $db_consent ) && $consent_status != 'Any' ) continue;
      
      $prov = $db_participant->get_primary_address()->get_region()->name;

      if( 'deceased' == $db_participant->status ||
          ( $province != 'All provinces' && $province != $prov ) ||
          ( $consent_status != 'Any' && $consent_status != $db_consent->event ) ) continue;

      $interview_mod = new db\modifier();
      $interview_mod->where( 'qnaire_id', '=', $db_qnaire->id ); 
      $db_interview = current( $db_participant->get_interview_list( $interview_mod ) );

      // TODO recover from Mastodon
      $age = 'TBD';
      $gender = 'TBD';
      $proxy = 'TBD';
      if( $db_interview && $db_interview->completed )
      {
        $contents[] = array(
          $db_participant->uid,
          $prov,
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
