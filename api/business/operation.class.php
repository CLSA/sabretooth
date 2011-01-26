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
  /**
   * Returns the associated database operation for the provided action.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $action
   * @return database\operation
   * @access public
   */
  public function get_db_operation( $action )
  {
    // create the associated database operation
    $primary_keys = array( 'name' => static::get_class_name(),
                           'action' => $action );
    return new \sabretooth\database\operation( $primary_keys );
  }

  /**
   * Determine whether the current user has access to an operation.
   * 
   * This method checks to see if the current user has access to the operation's
   * action at the user's current role.  The current user and role are determined by the session
   * singleton object.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $action
   * @return bool
   * @access public
   */
  public function has_access( $action )
  {
    // get the current role and test to see if it has access to this operation 
    return \sabretooth\session::self()->get_role()->has_operation(
             $this->get_db_operation( $action ) );
  }
}
?>
