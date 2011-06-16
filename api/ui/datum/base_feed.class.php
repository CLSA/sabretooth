<?php
/**
 * base_feed.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\datum;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all feed datums.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_feed extends \sabretooth\ui\datum
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'feed', $args );

    // set the start and end datetimes
    $this->start_datetime = $this->get_argument( 'start' );
    $this->end_datetime = $this->get_argument( 'end' );
  }
  
  /**
   * The start date/time of the feed
   * @var string
   * @access protected
   */
  protected $start_datetime = NULL;
  
  /**
   * The end date/time of the feed
   * @var string
   * @access protected
   */
  protected $end_datetime = NULL;
}
?>
