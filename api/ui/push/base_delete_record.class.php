<?php
/**
 * base_delete_record.class.php
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
 * Base class for all "delete_record" push operations.
 * 
 * @package sabretooth\ui
 */
abstract class base_delete_record extends base_record
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
    parent::__construct( $subject, 'delete_'.$child, $args );
    $this->child_subject = $child;
  }
  
  /**
   * Executes the push.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function finish()
  {
    try
    {
      $method_name = 'remove_'.$this->child_subject;
      $this->get_record()->$method_name( $this->get_argument( 'remove_id' ) );
    }
    catch( exc\database $e )
    { // help describe exceptions to the user
      if( $e->is_constrained() )
      {
        throw new exc\notice(
          'Unable to delete the '.$this->child_subject.
          ' because it is being referenced by the database.', __METHOD__, $e );
      }

      throw $e;
    }
  }

  /**
   * The list item's subject.
   * @var string
   * @access protected
   */
  protected $child_subject = '';
}
?>
