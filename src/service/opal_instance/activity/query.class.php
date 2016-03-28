<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\opal_instance\activity;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends parent class
 */
class query extends \cenozo\service\query
{
  /**
   * Extends parent method
   */
  protected function prepare()
  {
    parent::prepare();

    // the status will be 404, reset it to 200
    $this->status->set_code( 200 );
  }

  /**
   * Extends parent method
   */
  protected function get_record_count()
  {
    $modifier = clone $this->modifier;
    $this->select->apply_aliases_to_modifier( $modifier );
    return $this->get_parent_record()->get_user()->get_activity_count( $modifier );
  }

  /**
   * Extends parent method
   */
  protected function get_record_list()
  {
    $modifier = clone $this->modifier;
    $this->select->apply_aliases_to_modifier( $modifier );
    return $this->get_parent_record()->get_user()->get_activity_list( $this->select, $modifier );
  }
}
