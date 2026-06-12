<?php
/**
 * user.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\database;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * user: record
 */
class user extends \cenozo\database\user
{
  /**
   * Returns whether a user has signed up for the trainee_user
   */
  public function get_trainee_user()
  {
    $modifier = lib::create( 'database\modifier' );
    $modifier->join( 'trainee_user', 'user.id', 'trainee_user.user_id' );
    $modifier->where( 'user.id', '=', $this->id );
    return 0 < static::count( $modifier );
  }

  /**
   * Sets whether a user should be signed up for the trainee_user
   * @param boolean $trainee_user
   */
  public function set_trainee_user( $trainee_user )
  {
    if( $trainee_user )
    {
      return static::db()->execute( sprintf(
        'REPLACE INTO trainee_user SET user_id = %s',
        static::db()->format_string( $this->id )
      ) );
    }
    else
    {
      return static::db()->execute( sprintf(
        'DELETE FROM trainee_user WHERE user_id = %s',
        static::db()->format_string( $this->id )
      ) );
    }
  }
}
