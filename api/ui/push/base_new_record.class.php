<?php
/**
 * base_new_record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for all "new_record" push operations.
 * 
 * @package sabretooth\ui
 */
abstract class base_new_record extends base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The widget's subject.
   * @param string $child The list item's subject.
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $subject, $child, $args )
  {
    parent::__construct( $subject, 'new_'.$child, $args );
    $this->child_subject = $child;
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    $method_name = 'add_'.$this->child_subject;
    $this->get_record()->$method_name( $this->get_argument( 'id_list' ) );
  }

  /**
   * The list item's subject.
   * @var string
   * @access protected
   */
  protected $child_subject = '';
}
?>
