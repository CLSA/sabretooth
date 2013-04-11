<?php
/**
 * phone_list.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\widget;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * widget phone list
 */
class phone_list extends \cenozo\ui\widget\phone_list
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
    
    // only allow higher than first tier roles to make direct calls
    $this->set_variable( 'allow_connect',
                         1 < lib::create( 'business\session' )->get_role()->tier );
    $this->set_variable( 'sip_enabled',
      lib::create( 'business\voip_manager' )->get_sip_enabled() );
  }
}
