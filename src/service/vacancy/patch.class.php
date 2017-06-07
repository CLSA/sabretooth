<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\service\vacancy;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Special service for handling the patch meta-resource
 */
class patch extends \cenozo\service\patch
{
  /**
   * Override parent method
   */
  protected function validate()
  {
    parent::validate();

    // do not allow editing of vacancies which have appointments assigned to them
    $file = $this->get_file_as_array();
    $db_vacancy = $this->get_leaf_record();
    if( array_key_exists( 'datetime', $file ) && 0 < $db_vacancy->get_appointment_count() )
    {
      $this->set_data( 'Vacancy times can only be changed if they have no appointments.' );
      $this->get_status()->set_code( 306 );
    }
  }
}
