<?php
/**
 * questions.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\database
 * @filesource
 */

namespace sabretooth\database\limesurveys;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * questions: record
 *
 * @package sabretooth\database
 */
class questions extends record
{
  protected static $primary_key_name = 'qid';
}
?>
