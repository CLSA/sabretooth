<?php
/**
 * assignment.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * assignment: record
 *
 * @package sabretooth\database
 */
class assignment extends record
{
  /**
   * Gets the assignment's current phase.
   * Note: This method uses limesurvey's token management to determine the current phase.  It will
   *       create tokens in the limesurvey database as necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return phase (null if the interview is completed)
   * @access public
   */
  public function get_current_phase()
  {
    if( is_null( $this->id ) )
    {
      \sabretooth\log::warning( 'Tried to determine token for interview with no id.' );
      return NULL;
    } 
    
    // if the interview is complete then there is no valid token
    if( $this->get_interview()->completed ) return NULL;

    if( is_null( $this->current_phase ) )
    { // we haven't determined the current phase yet, do that now using tokens
      $modifier = new modifier();
      $modifier->order( 'stage' );
      
      foreach( $this->get_interview()->get_qnaire()->get_phase_list( $modifier ) as $db_phase )
      {
        $token = $this->get_token( $db_phase );

        $completed = limesurvey\record::db()->get_one(
          sprintf( 'SELECT completed FROM tokens_%s WHERE token = %s',
                   $db_phase->sid,
                   database::format_string( $token ) ) );

        if( is_null( $completed ) )
        { // token not found, create it
          limesurvey\record::db()->execute(
            sprintf( 'INSERT INTO tokens_%s SET token = %s',
                   $db_phase->sid,
                   database::format_string( $token ) ) );
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
        $db_interview = $this->get_interview();
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
   * @access private
   */
  private function get_token( $db_phase )
  {
    return is_null( $db_phase ) ?
      NULL : sprintf( "%s_%s_%s", 
                      $this->interview_id,
                      $db_phase->id,
                      // repeated phases have the assignment id as the last part of the token
                      $db_phase->repeated ? $this->id : 0 );
  }

  /**
   * This assignment's current phase
   * @var string
   * @access private
   */
  private $current_phase = NULL;
}
?>
