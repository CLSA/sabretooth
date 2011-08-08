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
      $phase_mod = new modifier();
      $phase_mod->order( 'rank' );
      
      $phase_list = $db_interview->get_qnaire()->get_phase_list( $phase_mod );
      if( 0 == count( $phase_list ) )
      {
        log::emerg( 'Questionnaire with no phases has been assigned.' );
        return NULL;
      }
      
      $db_participant = $db_interview->get_participant();

      foreach( $phase_list as $db_phase )
      {
        $token = $this->get_token( $db_phase );
        limesurvey\tokens::set_sid( $db_phase->sid );

        $tokens_mod = new modifier();
        $tokens_mod->where( 'token', '=', $token );
        $db_tokens = current( limesurvey\tokens::select( $tokens_mod ) );
        if( false === $db_tokens )
        { // token not found, create it
          $db_tokens = new limesurvey\tokens();
          $db_tokens->token = $token;
          $db_tokens->firstname = $db_participant->first_name;
          $db_tokens->lastname = $db_participant->last_name;

          // repeated phases require extra token information
          $db_tokens->update_attributes( $db_participant, $db_phase->repeated );
          $db_tokens->save();

          $this->current_phase = $db_phase;
          break;
        }
        else if( 'N' == $db_tokens->completed )
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
