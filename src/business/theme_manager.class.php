<?php
/**
 * theme_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Extends Cenozo's theme_manager class with custom functionality
 */
class theme_manager extends \cenozo\business\theme_manager
{
  /**
   * Extends parent method
   */
  protected function __construct()
  {
    // set the fullcalendar row height based on the vacancy size
    if( 30 > lib::create( 'business\setting_manager' )->get_setting( 'general', 'vacancy_size' ) )
    {
      $this->css_template .= "\n.fc-time-grid .fc-slats td { height: 3em; }";
    }

    parent::__construct();
  }
}
