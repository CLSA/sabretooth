<?php
/**
 * permission.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * permission: permission exceptions
 * 
 * All exceptions which are due to denied permissions, use this class to throw exceptions.
 * @package sabretooth\exception
 */
class permission extends base_exception
{
  /**
   * Constructor
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param database\operation $db_operation the associated operation
   * @param exception $previous the previous exception used for the exception chaining
   * @access public
   */
  public function __construct( $db_operation, $previous = NULL )
  {
    $this->operation = $db_operation;
    $message = is_null( $db_operation ) ||
               !is_object( $db_operation ) ||
               !is_subclass_of( $db_operation, '\\sabretooth\\database\\operation' )
             ? 'operation (unknown) denied'
             : sprintf( 'operation "%s".%s" denied.',
                        $db_operation->subject,
                        $db_operation->name );
    parent::__construct( $message, $previous );
  }

  /**
   * The operation which was denied
   * @var database\site
   * @access protected
   */
  protected $operation = NULL;
}
?>
