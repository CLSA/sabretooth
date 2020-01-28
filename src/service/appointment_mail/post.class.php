<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\appointment_mail;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    $session = lib::create( 'business\session' );
    $db_role = $session->get_role();
    $db_appointment_mail = $this->get_leaf_record();

    // force site_id if needed
    if( !$db_role->all_sites ) $db_appointment_mail->site_id = $session->get_site()->id;
  }
}
