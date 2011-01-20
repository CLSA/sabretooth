<?php
/**
 * operation.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\business
 */

namespace sabretooth\business;

/**
 * operation: abstract class for all operations
 *
 * All operation classes extend this base operation class.  Their purpose is to execute
 * business-logic, organized into 'operations'.  Each operation is a class in of itself,
 * which extends this class.
 * @package sabretooth\business
 */
abstract class operation extends \sabretooth\base_object
{
  public function set_action( $action )
  {
    return true;
  }

  /**
   * One of the 5 basic REVAL operations.
   * 
   * The method attempts to perform a remove operation on the object.
   * Though all REVAL methods exist for all operations, not all are valid.  Calling this method
   * when it is not marked in the `operation`.`reval` column for this operation will cause an
   * exception to be thrown.
   * @author TBD
   * @throws exception\runtime
   * @access public
   */
  public function remove()
  {
  }

  /**
   * One of the 5 basic REVAL operations.
   * 
   * The method attempts to perform a edit operation on the object.
   * Though all REVAL methods exist for all operations, not all are valid.  Calling this method
   * when it is not marked in the `operation`.`reval` column for this operation will cause an
   * exception to be thrown.
   * @author TBD
   * @throws exception\runtime
   * @access public
   */
  public function edit()
  {
  }

  /**
   * One of the 5 basic REVAL operations.
   * 
   * The method attempts to perform a view operation on the object.
   * Though all REVAL methods exist for all operations, not all are valid.  Calling this method
   * when it is not marked in the `operation`.`reval` column for this operation will cause an
   * exception to be thrown.
   * @author TBD
   * @throws exception\runtime
   * @access public
   */
  public function view()
  {
  }

  /**
   * One of the 5 basic REVAL operations.
   * 
   * The method attempts to perform a add operation on the object.
   * Though all REVAL methods exist for all operations, not all are valid.  Calling this method
   * when it is not marked in the `operation`.`reval` column for this operation will cause an
   * exception to be thrown.
   * @author TBD
   * @throws exception\runtime
   * @access public
   */
  public function add()
  {
  }

  /**
   * One of the 5 basic REVAL operations.
   * 
   * The method attempts to perform a list operation on the object.
   * Though all REVAL methods exist for all operations, not all are valid.  Calling this method
   * when it is not marked in the `operation`.`reval` column for this operation will cause an
   * exception to be thrown.
   * @author TBD
   * @throws exception\runtime
   * @access public
   */
  public function llist()
  {
  }
}
?>
