<?php
/**
 * base_calendar.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * base widget for all calendars
 * 
 * @package sabretooth\ui
 */
abstract class base_calendar extends widget
{
  /**
   * Constructor
   * 
   * Defines all variables required by the base calendar.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The calendar's subject.
   * @param array $args An associative array of arguments to be processed by the widget
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'calendar', $args );
  }
}
?>
