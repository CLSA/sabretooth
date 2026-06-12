<?php
/**
 * head.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\user;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all head services
 */
class head extends \cenozo\service\head
{
  /**
   * Extends parent method
   */
  protected function setup()
  {
    parent::setup();

    $this->columns['trainee_user'] = array(
      'data_type' => 'tinyint',
      'default' => '0',
      'required' => '1'
    );
  }
}
