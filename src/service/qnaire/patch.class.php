<?php
/**
 * patch.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\qnaire;
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

    // only allow the web version if the qnaire uses a Pine script
    $file = $this->get_file_as_array();
    if( array_key_exists( 'web_version', $file ) )
    {
      $not_pine = is_null( $this->get_leaf_record()->get_script()->pine_qnaire_id );
      if( $file['web_version'] && $not_pine )
      {
        $this->set_data( 'Only questionnaires linked to a Pine script can be run in web-version mode.' );
        $this->get_status()->set_code( 306 );
      }
    }
  }
}
