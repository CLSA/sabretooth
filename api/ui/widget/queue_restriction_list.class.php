<?php
/**
 * queue_restriction_list.class.php
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
 * widget queue_restriction list
 * 
 * @package sabretooth\ui
 */
class queue_restriction_list extends base_list_widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the queue_restriction list.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'queue_restriction', $args );
    
    $this->add_column( 'site.name', 'enum', 'Site', true );
    $this->add_column( 'city', 'string', 'City', true );
    $this->add_column( 'region.name', 'enum', 'Region', true );
    $this->add_column( 'postcode', 'string', 'Postcode', true );
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
      $db_site = $record->get_site();
      $db_region = $record->get_region();

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'site.name' => $db_site ? $db_site->name : 'any',
               'city' => $record->city ? $record->city : 'any',
               'region.name' => $db_region ? $db_region->name : 'any',
               'postcode' => $record->postcode ? $record->postcode : 'any' ) );
    }

    $this->finish_setting_rows();
  }
}
?>
