<?php
/**
 * head.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

class head extends \cenozo\service\head
{
  /**
   * Extends parent method
   */
  protected function execute()
  {
    parent::execute();

    // add details for the user record's active and name columns
    $user_class_name = lib::get_class_name( 'database\user' );
    $user_details = $user_class_name::db()->get_column_details( 'user' );
    $this->headers['Columns']['active'] = $user_details['active'];
    $this->headers['Columns']['username'] = $user_details['name'];
  }
}
