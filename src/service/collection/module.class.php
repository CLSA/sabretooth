<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\collection;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\collection\module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    if( 'qnaire' == $this->get_parent_subject() &&
        'collection' == $this->get_subject() &&
        false === $this->get_argument( 'choosing', false ) )
    {
      // remove the restriction on collection application
      $db_application = lib::create( 'business\session' )->get_application();
      $column = sprintf( 'IFNULL( application_has_collection.application_id, %d )', $db_application->id );
      $modifier->remove_where( $column );
    }
  }
}
