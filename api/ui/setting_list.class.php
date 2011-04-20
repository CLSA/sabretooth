<?php
/**
 * setting_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * widget setting list
 * 
 * @package sabretooth\ui
 */
class setting_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the setting list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'setting', $args );
    
    $this->add_column( 'category', 'string', 'Category', true );
    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'value', 'string', 'Value', true );
    $this->add_column( 'description', 'text', 'Description', true, 'left' );
  }

  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    parent::finish();
    
    foreach( $this->get_record_list() as $record )
    {
      $this->add_row( $record->id,
        array( 'category' => $record->category,
               'name' => $record->name,
               'value' => $record->value,
               'description' => $record->description ) );
    }

    $this->finish_setting_rows();
  }
}
?>
