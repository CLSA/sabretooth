<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\user;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\user\post
{
  /**
   * Replace parent method
   */
  protected function execute()
  {
    parent::execute();

    if( $this->may_continue() )
    {
      $post_object = $this->get_file_as_object();
      if( property_exists( $post_object, 'trainee_user' ) )
      {
        $db_user = $this->get_leaf_record();
        $db_user->set_trainee_user( $post_object->trainee_user );
      }
    }
  }
}
