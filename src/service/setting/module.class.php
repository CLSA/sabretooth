<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\setting;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\setting\module
{
  /**
   * Extend parent method
   */
  public function validate()
  {
    parent::validate();

    if( $this->service->may_continue() )
    {
      if( 'PATCH' == $this->get_method() )
      {
        $data_array = $this->get_file_as_array();
        if(
          is_array( $data_array ) &&
          array_key_exists( 'appointment_duration', $data_array ) &&
          0 != $data_array['appointment_duration'] % 30
        ) {
          $this->get_status()->set_code( 306 );
          $this->set_data(
            'The default appointment duration must be in 30 minute increments only (30, 60, 90, etc).'
          );
        }
      }
    }
  }
}
