<?php
/**
 * users.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurveys;

/**
 * users: active record
 *
 * @package sabretooth\database
 */
class users extends active_record
{
  protected static $primary_key_name = 'uid';
}
?>
