<?php
/**
 * setting_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\widget;
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
    
    $is_supervisor = 'supervisor' == bus\session::self()->get_role()->name;

    $this->add_column( 'category', 'string', 'Category', true );
    $this->add_column( 'name', 'string', 'Name', true );
    $this->add_column( 'value', 'string', 'Default', false );
    if( $is_supervisor ) $this->add_column( 'site_value', 'string', 'Value', false );
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
    
    $is_supervisor = 'supervisor' == bus\session::self()->get_role()->name;


    foreach( $this->get_record_list() as $record )
    {
      if( $is_supervisor )
      { // include the site's value
        $modifier = new db\modifier();
        $modifier->where( 'site_id', '=', bus\session::self()->get_site()->id );
        $setting_value_list = $record->get_setting_value_list( $modifier );
        $value = 1 == count( $setting_value_list ) ? $setting_value_list[0]->value : '';

        $this->add_row( $record->id,
          array( 'category' => $record->category,
                 'name' => $record->name,
                 'value' => $record->value,
                 'site_value' => $value,
                 'description' => $record->description ) );
      }
      else
      {
        $this->add_row( $record->id,
          array( 'category' => $record->category,
                 'name' => $record->name,
                 'value' => $record->value,
                 'description' => $record->description ) );
      }
    }

    $this->finish_setting_rows();
  }
}
?>
