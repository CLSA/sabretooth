<?php
/**
 * quota_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget quota list
 */
class quota_list extends \cenozo\ui\widget\quota_list
{
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
    
    $this->add_column( 'quota_state.disabled', 'boolean', 'Disabled', true );
  }
  
  /**
   * Set the rows array needed by the template.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    // skip the parent method
    $grand_parent = get_parent_class( get_parent_class( get_class() ) );
    $grand_parent::setup();
    
    foreach( $this->get_record_list() as $record )
    {
      $db_age_group = $record->get_age_group();

      // assemble the row for this record
      $this->add_row( $record->id,
        array( 'site.name' => $record->get_site()->get_full_name(),
               'region.name' => $record->get_region()->name,
               'gender' => $record->gender,
               'age_group.lower' => $db_age_group->to_string(),
               'population' => $record->population,
               'quota_state.disabled' => $record->state_disabled ) );
    }
  }
}
