<?php
/**
 * query.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\assignment;
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

    // add the assignment's last call's status column
    $this->modifier->cross_join( 'assignment_last_phone_call',
      'assignment.id', 'assignment_last_phone_call.assignment_id' );
    $this->modifier->cross_join( 'phone_call AS last_phone_call',
      'assignment_last_phone_call.phone_call_id', 'last_phone_call.id' );
    $this->select->add_table_column( 'last_phone_call', 'status' );
  }
}
