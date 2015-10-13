<?php
/**
 * tokens.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class tokens extends \cenozo\database\limesurvey\tokens
{
  /**
   * Extends parent method
   */
  public static function determine_token_string( $db_participant, $repeated )
  {
    $postfix = NULL;

    if( $repeated )
    { // check for an open assignment as the postfix
      $select = lib::create( 'database\select' );
      $select->add_column( 'id' );
      $modifier = lib::create( 'database\modifier' );
      $modifier->join( 'interview', 'assignment.interview_id', 'interview.id' );
      $modifier->where( 'interview.participant_id', '=', $db_participant->id );
      $modifier->where( 'assignment.end_datetime', '=', NULL );

      $assignment_id_list = 
        lib::create( 'business\session' )->get_user()->get_assignment_list( $select, $modifier );
      if( 0 < count( $assignment_id_list ) )
      {
        $postfix = str_pad(
          current( $assignment_id_list )['id'],
          self::TOKEN_POSTFIX_LENGTH, '0', STR_PAD_LEFT );
      }
    }

    return is_null( $postfix ) ? parent::determine_token_string( $db_participant, $repeated )
                               : sprintf( '%s.%s', $db_participant->uid, $postfix );
  }
}
