<?php
/**
 * participant_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget participant list
 */
class participant_list extends \cenozo\ui\widget\participant_list
{
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    // include the sync action if the widget isn't parented
    if( is_null( $this->parent ) )
    {
      $operation_class_name = lib::get_class_name( 'database\operation' );
      $db_operation = $operation_class_name::get_operation( 'widget', 'participant', 'sync' );
      if( lib::create( 'business\session' )->is_allowed( $db_operation ) )
        $this->add_action( 'sync', 'Participant Sync', $db_operation,
          'Synchronize participants with Mastodon' );
    }
  }
}
