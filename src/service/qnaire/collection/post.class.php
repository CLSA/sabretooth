<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire\collection;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Replace parent method
   */
  protected function validate()
  {
    parent::validate();
    
    $db_role = lib::create( 'business\session' )->get_role();

    // ignore the 403, it happens when adding a locked collection to the qnaire
    if( 2 < $db_role->tier && 403 == $this->get_status()->get_code() )
      $this->get_status()->set_code( 200 );
  }

  /**
   * Replace parent method
   */
  protected function finish()
  {
    parent::finish();

    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::repopulate();
  }
}
