<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\user;
use cenozo\lib, cenozo\log, sabretooth\util;

class patch extends \cenozo\service\user\patch
{
  /**
   * Override parent method
   */
  protected function prepare()
  {
    $this->extract_parameter_list[] = 'trainee_user';

    parent::prepare();
  }

  /**
   * Override parent method
   */
  protected function execute()
  {
    parent::execute();

    $trainee_user = $this->get_argument( 'trainee_user', NULL );
    if( NULL !== $trainee_user )
    {
      $db_user = $this->get_leaf_record();
      $db_user->set_trainee_user( $trainee_user );
    }
  }
}
