<?php
/**
 * opal_instance_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget opal_instance list
 */
class opal_instance_list extends \cenozo\ui\widget\base_list
{
  /**
   * Constructor
   * 
   * Defines all variables required by the opal_instance list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'opal_instance', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();
    
    $this->add_column( 'user.name', 'string', 'Name', false );
    $this->add_column( 'active', 'boolean', 'Active', true );
    $this->add_column( 'last_activity', 'fuzzy', 'Last activity', false );
  }
  
  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_user = $record->get_user();

      // determine the last activity
      $db_activity = $db_user->get_last_activity();
      $last = is_null( $db_activity ) ? null : $db_activity->datetime;

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'user.name' => $db_user->name,
               'active' => $db_user->active,
               'last_activity' => $last ) );
    }
  }
}
?>
