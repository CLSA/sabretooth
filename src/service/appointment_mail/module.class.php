<?php
/**
 * module.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\service\appointment_mail;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Performs operations which effect how this module is used in a service
 */
class module extends \cenozo\service\site_restricted_module
{
  /**
   * Extend parent method
   */
  public function prepare_read( $select, $modifier )
  {
    parent::prepare_read( $select, $modifier );

    $modifier->join( 'site', 'appointment_mail.site_id', 'site.id' );
    $modifier->join( 'language', 'appointment_mail.language_id', 'language.id' );

    if( $select->has_column( 'delay' ) )
    {
      $select->add_column(
        'IF( '.
          '"immediately" = appointment_mail.delay_unit, '.
          '"Immediately", '.
          'IF( '.
            '1 = appointment_mail.delay_offset, '.
            '"1 Day", '.
            'CONCAT( appointment_mail.delay_offset, " Days" ) '.
          ') '.
        ')',
        'delay',
        false
      );
    }

    $db_mail_template = $this->get_resource();
    if( !is_null( $db_mail_template ) )
    {
      if( $select->has_column( 'validate' ) ) $select->add_constant( $db_mail_template->validate(), 'validate' );
    }
  }
}
