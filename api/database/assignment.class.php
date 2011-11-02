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
   * This method returns the current SID, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_current_sid()
  {
    if( is_null( $this->current_sid ) ) $this->determine_current_sid_and_token();
    return $this->current_sid;
  }

  /**
   * This method returns the current token, or false if all surveys are complete.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_current_token()
  {
    if( is_null( $this->current_token ) ) $this->determine_current_sid_and_token();
    return $this->current_token;
  }

  /**
   * Determines the current SID and token.
   * 
   * This method will first determine whether the participant needs to complete the withdraw
   * script or a questionnaire.  It then determines whether the appropriate script has been
   * completed or not.
   * Note: This method will create tokens in the limesurvey database as necessary.
   * This is also where interviews are marked as complete once all phases are finished.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function determine_current_sid_and_token()
  {
    if( is_null( $this->id ) )
    {
      log::warning( 'Tried to determine current phase for assignment with no id.' );
      return false;
    }
    
    $this->current_sid = false;
    $this->current_token = false;

    // records which we will need
    $db_interview = $this->get_interview();
    $db_participant = $db_interview->get_participant();
    $db_consent = $db_participant->get_last_consent();
    
    if( $db_consent && 'withdraw' == $db_consent->event )
    { // the participant has withdrawn, check to see if the withdraw script is complete
      $db_qnaire = $db_interview->get_qnaire();
      
      // let the tokens record class know which SID we are dealing with
      limesurvey\tokens::set_sid( $db_qnaire->withdraw_sid );

      $token = limesurvey\tokens::determine_token_string( $db_interview );
      $tokens_mod = new modifier();
      $tokens_mod->where( 'token', '=', $token );
      $db_tokens = current( limesurvey\tokens::select( $tokens_mod ) );

      if( false === $db_tokens )
      { // token not found, create it
        $db_tokens = new limesurvey\tokens();
        $db_tokens->token = $token;
        $db_tokens->firstname = $db_participant->first_name;
        $db_tokens->lastname = $db_participant->last_name;
        $db_tokens->update_attributes( $db_participant );
        $db_tokens->save();

        $this->current_sid = $db_qnaire->withdraw_sid;
        $this->current_token = $token;
      }
      else if( 'N' == $db_tokens->completed )
      {
        $this->current_sid = $db_qnaire->withdraw_sid;
        $this->current_token = $token;
      }
    }
    else
    { // the participant has not withdrawn, check each phase of the interview
      $phase_mod = new modifier();
      $phase_mod->order( 'rank' );
      
      $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );
      if( 0 == count( $phase_list ) )
      {
        log::emerg( 'Questionnaire with no phases has been assigned.' );
      }
      else
      {
        foreach( $phase_list as $db_phase )
        {
          // let the tokens record class know which SID we are dealing with
          limesurvey\tokens::set_sid( $db_phase->sid );
  
          $token = limesurvey\tokens::determine_token_string(
                     $db_interview,
                     $db_phase->repeated ? $this : NULL );
          $tokens_mod = new modifier();
          $tokens_mod->where( 'token', '=', $token );
          $db_tokens = current( limesurvey\tokens::select( $tokens_mod ) );
  
          if( false === $db_tokens )
          { // token not found, create it
            $db_tokens = new limesurvey\tokens();
            $db_tokens->token = $token;
            $db_tokens->firstname = $db_participant->first_name;
            $db_tokens->lastname = $db_participant->last_name;
            $db_tokens->update_attributes( $db_participant );
            $db_tokens->save();
  
            $this->current_sid = $db_phase->sid;
            $this->current_token = $token;
            break;
          }
          else if( 'N' == $db_tokens->completed )
          { // we have found the current phase
            $this->current_sid = $db_phase->sid;
            $this->current_token = $token;
            break;
          }
        }
      }

      // complete the interview if all phases are complete
      if( false === $this->current_sid )
      {
        $db_interview->completed = true;
        $db_interview->save();
      }
    }
  }
  
  /**
   * This assignment's current sid
   * @var int
   * @access private
   */
  private $current_sid = NULL;
  
  /**
   * This assignment's current token
   * @var string
   * @access private
   */
  private $current_token = NULL;
}
?>
