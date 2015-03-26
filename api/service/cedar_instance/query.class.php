<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\cedar_instance;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * The base class of all query (collection-based get) services
 */
class query extends \cenozo\service\query
{
  /**
   * Processes arguments, preparing them for the service.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // link to the cedar instance's user and last activity and add the activity's datetime
    $this->modifier->join( 'user', 'cedar_instance.user_id', 'user.id' );
    $this->modifier->left_join( 'user_last_activity', 'user.id', 'user_last_activity.user_id' );
    $this->modifier->left_join(
      'activity', 'user_last_activity.activity_id', 'last_activity.id', 'last_activity' );
    $this->select->add_table_column( 'last_activity', 'datetime' );
  }
}
