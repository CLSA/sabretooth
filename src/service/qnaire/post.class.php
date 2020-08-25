<?php
/**
 * post.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire;
use cenozo\lib, cenozo\log, sabretooth\util;

class post extends \cenozo\service\post
{
  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    // only allow the web version if the qnaire uses a Pine script
    $file = $this->get_file_as_array();
    $not_pine = is_null( lib::create( 'database\script', $file['script_id'] )->pine_qnaire_id );
    if( $file['web_version'] && $not_pine )
    {
      $this->set_data( 'Only questionnaires linked to a Pine script can be run in web-version mode.' );
      $this->get_status()->set_code( 306 );
    }
  }

  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    // repopulate the queues immediately
    $queue_class_name = lib::get_class_name( 'database\queue' );
    $queue_class_name::delayed_repopulate();
  }
}
