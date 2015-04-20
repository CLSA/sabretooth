<?php
/**
 * ui.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Application extension to ui class
 */
class ui extends \cenozo\ui\ui
{
  /**
   * Extends the parent method
   */
  protected function get_module_list( $modifier = NULL )
  {
    $module_list = parent::get_module_list( $modifier );

    // add child actions to certain modules
    if( array_key_exists( 'qnaire', $module_list ) )
      $module_list['qnaire']['children'] = array( 'phase' );

    return $module_list;
  }

  /**
   * Extends the parent method
   */
  protected function get_list_items()
  {
    $list = parent::get_list_items();
    
    // add application-specific states to the base list
    $list['assignment'] = 'Assignments';
    $list['interview'] = 'Interviews';
    $list['opal_instance'] = 'Opal Instances';
    $list['qnaire'] = 'Questionnaires';
    $list['queue'] = 'Queues';

    return $list;
  }
}
