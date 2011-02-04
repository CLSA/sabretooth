<?php
/**
 * role.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * role: active record
 *
 * @package sabretooth\database
 */
class role extends active_record
{
  /**
   * Returns whether the user has the role for the given site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\operation $db_operation
   * @return bool
   */
  public function has_operation( $db_operation )
  {
    $result = false;

    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
    }
    else
    {
      $rows = self::get_one(
        'SELECT * '.
        'FROM role_has_operation '.
        'WHERE role_id = '.$this->id.' '.
        'AND type = "'.$db_operation->type.'" '.
        'AND subject = "'.$db_operation->subject.'" '.
        'AND name = "'.$db_operation->name.'"' );
      $result = 0 < count( $rows );
    }

    return $result;
  }
  
  /**
   * Get the number of users that have this role at any site.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return int
   * @access public
   */
  public function get_user_count()
  {
    $count = 0;
    if( !$this->are_primary_keys_set() )
    {
      \sabretooth\log::warning( 'Tried to determine operation for record with no id' );
    }
    else
    {
      $count = self::get_one(
        'SELECT COUNT( DISTINCT user_id) '.
        'FROM user_access '.
        'WHERE role_id = '.$this->id );
    }

    return $count;
  }
}
?>
