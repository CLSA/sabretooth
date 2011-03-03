<?php
/**
 * relationship.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database;

/**
 * This is an enum class which defines all types of database table relationships.
 * 
 * @package sabretooth\database
 */
class relationship
{
  const NONE = 0;
  const ONE_TO_ONE = 1;
  const ONE_TO_MANY = 2;
  const MANY_TO_MANY = 3;
}
?>
