<?php
/**
 * tokens.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Access to limesurvey's tokens_SID tables.
 * 
 * @package sabretooth\database
 */
class tokens extends sid_record
{
  /**
   * Updates the token attributes with current values from Mastodon
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param db\participant $db_participant The record of the participant linked to this token.
   * @param boolean $extended Whether or not to included extended parameters.
   * @access public
   */
  public function update_attributes( $db_participant, $extended = false )
  {
    $mastodon_manager = bus\mastodon_manager::self();
    $db_user = bus\session::self()->get_user();
    
    if( $mastodon_manager->is_enabled() )
    { // get attributes from mastodon
      
      // get the participant's information
      $participant_info = $mastodon_manager->pull(
        'participant', 'primary', array( 'uid' => $db_participant->uid ) );
      $consent_info = $mastodon_manager->pull(
        'participant', 'list_consent', array( 'uid' => $db_participant->uid ) );
      
      $written_consent = false;
      foreach( $consent_info->data as $consent )
      {
        if( 'written' == substr( $consent->event, 0, 7 ) )
        {
          $written_consent = true;
          break;
        }
      }
    }
    else
    { // get attributes from sabretooth
      $db_address = $db_participant->get_primary_address();
      if( is_null( $db_address ) )
      {
        $participant_info->data->street = "";
        $participant_info->data->city = "";
        $participant_info->data->region = "";
        $participant_info->data->postcode = "";
      }
      else
      {
        $participant_info->data->street = $db_address->address1;
        if( !is_null( $db_address->address2 ) )
          $participant_info->data->street .= ' '.$db_address->address2;
        $participant_info->data->city = $db_address->city;
        $participant_info->data->region = $db_address->get_region()->get_name();
        $participant_info->data->postcode = $db_address->postcode;
      }

      // written consent received
      $consent_mod = new modifier();
      $consent_mod->where( 'event', 'like', 'written %' );
      $written_consent = 0 < $db_participant->get_consent_count( $consent_mod );

      // sabretooth doesn't track the following information
      $participant_info->data->date_of_birth = "";
      $participant_info->data->email = "";
      $participant_info->data->hin_access = "";
      $participant_info->data->prior_contact_date = "";
    }

    if( !$extended )
    {
      // age
      if( $mastodon_manager->is_enabled() )
      { // get age from mastodon
        $dob = util::get_datetime_object( $participant_info->data->date_of_birth );
        $this->attribute_1 = util::get_interval( $dob )->y;
      }
      else
      {
        // sabretooth doesn't track date of birth or age
        $this->attribute_1 = "";
      }

      // written_consent determined above
      $this->attribute_2 = $written_consent;
    }
    else
    {
      // get the participant's alternate contact information (if using mastodon)
      if( $mastodon_manager->is_enabled() )
      {
        $alternate_info = $mastodon_manager->pull(
          'participant', 'list_alternate', array( 'uid' => $db_participant->uid ) );
      }
      else
      {
        $alternate_info->data = array();
      }

      // email address
      $this->attribute_1 = $participant_info->data->email;
      
      // address
      $this->attribute_2 = $participant_info->data->street;
      
      // city
      $this->attribute_3 = $participant_info->data->city;
      
      // province
      $this->attribute_4 = $participant_info->data->region;
      
      // postcode
      $this->attribute_5 = $participant_info->data->postcode;
      
      // age
      $dob = util::get_datetime_object( $participant_info->data->date_of_birth );
      $this->attribute_6 = util::get_interval( $dob )->y;
      
      // written consent received (determined above)
      $this->attribute_6 = $written_consent;
      
      // consented to provide HIN
      $this->attribute_7 = true == $participant_info->data->hin_access;
      
      // operator's firstname
      $this->attribute_8 = $db_user->first_name;
      
      // operator's lastname
      $this->attribute_9 = $db_user->last_name;
      
      // previous CCHS contact date
      $this->attribute_10 = $participant_info->data->prior_contact_date;
      
      // number of alternate contacts
      $number_of_alts = count( $alternate_info->data );
      $this->attribute_11 = $number_of_alts;
      
      // add the first alternate contact
      if( 0 < $number_of_alts )
      {
        // alternate's firstname
        $this->attribute_12 = $alternate_info->data[0]->first_name;
        
        // alternate's lastname
        $this->attribute_13 = $alternate_info->data[0]->last_name;
        
        // alternate's phone
        $this->attribute_14 = $alternate_info->data[0]->phone;
      }
      
      // add the second alternate contact
      if( 1 < $number_of_alts )
      {
        // alternate's firstname
        $this->attribute_15 = $alternate_info->data[1]->first_name;
        
        // alternate's lastname
        $this->attribute_16 = $alternate_info->data[1]->last_name;
        
        // alternate's phone
        $this->attribute_17 = $alternate_info->data[1]->phone;
      }
    }
  }

  /**
   * Returns the token name for a particular interview.
   * If the survey's phase is repeated then the assignment must also be provided.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\interview $db_interview 
   * @param database\interview $db_assignment (only used if the phase is repeated)
   * @static
   * @access public
   */
  public static function determine_token_string( $db_interview, $db_assignment = NULL )
  {
    return sprintf( '%s_%s',
                    $db_interview->id,
                    is_null( $db_assignment ) ? 0 : $db_assignment->id );
  }

  /**
   * The name of the table's primary key column.
   * @var string
   * @access protected
   */
  protected static $primary_key_name = 'tid';
}
?>
