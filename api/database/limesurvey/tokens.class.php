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
  public function update_attributes( $db_participant )
  {
    $mastodon_manager = bus\mastodon_manager::self();
    $db_user = bus\session::self()->get_user();

    // determine the first part of the token
    $db_interview = bus\session::self()->get_current_assignment()->get_interview();
    $token_part = substr( static::determine_token_string( $db_interview ), 0, -1 );
    
    // try getting the attributes from mastodon or sabretooth
    if( $mastodon_manager->is_enabled() )
    {
      $participant_info = $mastodon_manager->pull(
        'participant', 'primary', array( 'uid' => $db_participant->uid ) );
      $consent_info = $mastodon_manager->pull(
        'participant', 'list_consent', array( 'uid' => $db_participant->uid ) );
      $alternate_info = $mastodon_manager->pull(
        'participant', 'list_alternate', array( 'uid' => $db_participant->uid ) );
      
      // written consent received
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
    {
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
      $consent_mod = new db\modifier();
      $consent_mod->where( 'event', 'like', 'written %' );
      $written_consent = 0 < $db_participant->get_consent_count( $consent_mod );

      // sabretooth doesn't track the following information
      $participant_info->data->date_of_birth = "";
      $participant_info->data->email = "";
      $participant_info->data->hin_access = "";
      $participant_info->data->prior_contact_date = "";
      $alternate_info->data = array();
    }
    
    // determine the attributes from the survey with the same ID
    $db_surveys = new surveys( static::$table_sid );

    foreach( explode( "\n", $db_surveys->attributedescriptions ) as $attribute )
    {
      if( 10 < strlen( $attribute ) )
      {
        $key = 'attribute_'.substr( $attribute, 10, strpos( $attribute, '=' ) - 10 );
        $value = substr( $attribute, strpos( $attribute, '=' ) + 1 );
        $matches = array(); // for pregs below
        
        // now get the info based on the attribute name
        if( 'address street' == $value )
        {
          $this->$key = $participant_info->data->street;
        }
        else if( 'address city' == $value )
        {
          $this->$key = $participant_info->data->city;
        }
        else if( 'address province' == $value )
        {
          $this->$key = $participant_info->data->region;
        }
        else if( 'address postal code' == $value )
        {
          $this->$key = $participant_info->data->postcode;
        }
        else if( 'age' == $value )
        {
          $this->$key = strlen( $participant_info->data->date_of_birth )
                      ? util::get_interval(
                          util::get_datetime_object( $participant_info->data->date_of_birth ) )->y
                      : "";
        }
        else if( 'written consent received' == $value )
        {
          $this->$key = $written_consent ? "1" : "0";
        }
        else if( 'consented to provide HIN' == $value )
        {
          $this->$key = true == $participant_info->data->hin_access ? "1" : "0";
        }
        else if( 'operator first_name' == $value )
        {
          $this->$key = $db_user->first_name;
        }
        else if( 'operator last_name' == $value )
        {
          $this->$key = $db_user->last_name;
        }
        else if( 'previous CCHS contact date' == $value )
        {
          $this->$key = $participant_info->data->prior_contact_date;
        }
        else if( 'number of alternate contacts' == $value )
        {
          $this->$key = count( $alternate_info->data );
        }
        else if(
          preg_match( '/alternate([0-9]+) (first_name|last_name|phone)/', $value, $matches ) )
        {
          $alt_number = intval( $matches[1] );
          $aspect = $matches[2];

          $this->$key = $alt_number <= count( $alternate_info->data )
                      ? $alternate_info->data[$alt_number - 1]->$aspect
                      : "";
        }
        else if( 'previously completed' == $value )
        {
          // no need to set the token sid since it should already be set before calling this method
          $tokens_mod = new db\modifier();
          $tokens_mod->where( 'token', 'like', $token_part.'%' );
          $tokens_mod->where( 'completed', '!=', 'N' );
          $this->$key = static::count( $tokens_mod );
        }
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
