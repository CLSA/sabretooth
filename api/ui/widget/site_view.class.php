<?php
/**
 * site_view.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget site view
 */
class site_view extends \cenozo\ui\widget\site_view
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

    // add in the voip details
    $this->add_item( 'voip_host', 'string', 'Voip Host' );
    $this->add_item( 'voip_xor_key', 'string', 'Voip XOR Key' );
  }

  /**
   * Defines all items in the view.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $record = $this->get_record();
    
    // set the view's items
    $this->set_item( 'voip_host', $record->voip_host );
    $this->set_item( 'voip_xor_key', $record->voip_xor_key );
  }
}
