<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\exception as exc;

/**
 * assignment: record
 *
 * @package sabretooth\database
 */
class assignment extends has_note
{
  /**
   * Gets the assignment's current phase.
   * Note: This method uses limesurvey's token management to determine the current phase.  It will
   *       create tokens in the limesurvey database as necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return phase (null if the interview is completed)
   * @throws exception\runtime
   * @access public
   */
  public function get_current_phase()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine current phase for assignment with no id.' );
      return NULL;
    } 
    
    $db_interview = $this->get_interview();

    // if the interview is complete then there is no valid token
    if( $db_interview->completed ) return NULL;

    if( is_null( $this->current_phase ) )
    { // we haven't determined the current phase yet, do that now using tokens
      $modifier = new modifier();
      $modifier->order( 'rank' );
      
      $phase_list = $db_interview->get_qnaire()->get_phase_list( $modifier );
      if( 0 == count( $phase_list ) )
      {
        log::emerg( 'Questionnaire with no phases has been assigned.' );
        return NULL;
      }
      
      $db_participant = $db_interview->get_participant();
      $db_site = bus\session::self()->get_site();
      $db_role = bus\session::self()->get_role();
      $db_user = bus\session::self()->get_user();

      foreach( $phase_list as $db_phase )
      {
        $token = $this->get_token( $db_phase );
        $tokens_table = sprintf( '%stokens_%d',
          bus\setting_manager::self()->get_setting( 'survey_db', 'prefix' ),
          $db_phase->sid );

        $completed = limesurvey\record::db()->get_one(
          sprintf( 'SELECT completed FROM %s WHERE token = %s',
                   $tokens_table,
                   database::format_string( $token ) ) );

        if( is_null( $completed ) )
        { // token not found, create it

          // set the token string, first name, last name and email for all tokens
          $sets = sprintf( 'token = %s', database::format_string( $token ) );
          $sets .= sprintf( ', firstname = %s', database::format_string(
            $db_participant->first_name ) );
          $sets .= sprintf( ', lastname = %s', database::format_string(
            $db_participant->last_name ) );

          // repeated phases require extra token information
          if( $db_phase->repeated )
          {
            // determine mastodon's base url (using basic authentication)
            $base_url = SABRETOOTH_URL.'/'.MASTODON_URL.'/';
            $base_url = preg_replace(
              '#://#', '://'.$_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'].'@', $base_url );

            $request = new \HttpRequest();
            $request->enableCookies();
            
            // set the site
            $request->setUrl( $base_url.'self/set_site' );
            $request->setMethod( \HttpRequest::METH_POST );
            $request->setPostFields( array( 'name' => $db_site->name, 'cohort' => 'tracking' ) );

            if( 200 != $request->send()->getResponseCode() )
              throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );
            
            // set the role
            $request->setUrl( $base_url.'self/set_role' );
            $request->setMethod( \HttpRequest::METH_POST );
            $request->setPostFields( array( 'name' => $db_role->name ) );
            if( 200 != $request->send()->getResponseCode() )
              throw new exc\runtime( 'Unable to connect to Mastodon', __METHOD__ );
            
            // get the participant's primary information
            $request->setUrl( $base_url.'participant/primary' );
            $request->setMethod( \HttpRequest::METH_GET );
            $request->setQueryData( array( 'uid' => $db_participant->uid ) );
            $message = $request->send();
            if( 200 != $message->getResponseCode() )
              throw new exc\runtime( 'Unable to fetch participant info from Mastodon', __METHOD__ );
            $participant_info = json_decode( $message->getBody() );
            
            // get the participant's consent information
            $request->setUrl( $base_url.'participant/list_consent' );
            $request->setMethod( \HttpRequest::METH_GET );
            $request->setQueryData( array( 'uid' => $db_participant->uid ) );
            $message = $request->send();
            if( 200 != $message->getResponseCode() )
              throw new exc\runtime( 'Unable to fetch consent info from Mastodon', __METHOD__ );
            $consent_info = json_decode( $message->getBody() );
            
            // get the participant's alternate contact information
            $request->setUrl( $base_url.'participant/list_alternate' );
            $request->setMethod( \HttpRequest::METH_GET );
            $request->setQueryData( array( 'uid' => $db_participant->uid ) );
            $message = $request->send();
            if( 200 != $message->getResponseCode() )
              throw new exc\runtime( 'Unable to fetch alternate info from Mastodon', __METHOD__ );
            $alternate_info = json_decode( $message->getBody() );
            
            // email address
            $sets .= sprintf( ', email = %s', database::format_string(
              $participant_info->data->email ) );
            
            // address
            $sets .= sprintf( ', attribute_1 = %s', database::format_string(
              $participant_info->data->street ) );
            
            // city
            $sets .= sprintf( ', attribute_2 = %s', database::format_string(
              $participant_info->data->city ) );
            
            // province
            $sets .= sprintf( ', attribute_3 = %s', database::format_string(
              $participant_info->data->region ) );
            
            // postcode
            $sets .= sprintf( ', attribute_4 = %s', database::format_string(
              $participant_info->data->postcode ) );
            
            // age
            $dob = util::get_datetime_object( $participant_info->data->date_of_birth );
            $sets .= sprintf( ', attribute_5 = %s', database::format_string(
              util::get_interval( $dob )->y ) );
            
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
            $sets .= sprintf( ', attribute_6 = %s', database::format_string(
              $written_consent ) );
            
            // consented to provide HIN
            $sets .= sprintf( ', attribute_7 = %s', database::format_string(
              true == $participant_info->data->hin_access ) );
            
            // operator's firstname
            $sets .= sprintf( ', attribute_8 = %s', database::format_string(
              $db_user->first_name ) );
            
            // operator's lastname
            $sets .= sprintf( ', attribute_9 = %s', database::format_string(
              $db_user->last_name ) );
            
            // previous CCHS contact date
            $sets .= sprintf( ', attribute_10 = %s', database::format_string(
              $participant_info->data->prior_contact_date ) );
            
            // number of alternate contacts
            $number_of_alts = count( $alternate_info->data );
            $sets .= sprintf( ', attribute_11 = %s', database::format_string(
              $number_of_alts ) );
            
            if( 0 < $number_of_alts )
            {
              // alternate's firstname
              $sets .= sprintf( ', attribute_12 = %s', database::format_string(
                $alternate_info->data[0]->first_name ) );
              
              // alternate's lastname
              $sets .= sprintf( ', attribute_13 = %s', database::format_string(
                $alternate_info->data[0]->last_name ) );
              
              // alternate's phone
              $sets .= sprintf( ', attribute_14 = %s', database::format_string(
                $alternate_info->data[0]->phone ) );
            }
          }
          
          limesurvey\record::db()->execute(
            sprintf( 'INSERT INTO %s SET %s', $tokens_table, $sets ) );
          $this->current_phase = $db_phase;
          break;
        }
        else if( 'N' == $completed )
        { // we have found the current phase
          $this->current_phase = $db_phase;
          break;
        }
      }

      if( is_null( $this->current_phase ) )
      { // all phases are complete
        $db_interview->completed = true;
        $db_interview->save();
      }
    }

    return $this->current_phase;
  }
  
  /**
   * Gets the assignment's current limesurvey token.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string (null if the interview is completed)
   * @access public
   */
  public function get_current_token()
  {
    return $this->get_token( $this->get_current_phase() );
  }

  /**
   * Gets a token for a particular phase of this assignment
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_token( $db_phase )
  {
    return is_null( $db_phase ) ?
      NULL : limesurvey\record::get_token( $this->get_interview(), $db_phase, $this );
  }

  /**
   * This assignment's current phase
   * @var string
   * @access private
   */
  private $current_phase = NULL;
}
?>
