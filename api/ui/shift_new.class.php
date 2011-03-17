<?php
/**
 * shift_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * action shift new
 *
 * Create a new shift.
 * @package sabretooth\ui
 */
class shift_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }

  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    // make sure the date column isn't blank
    $columns = $this->get_argument( 'columns' );
    if( !array_key_exists( 'date', $columns ) || 0 == strlen( $columns['date'] ) )
      throw new \sabretooth\exception\notice( 'The date cannot be left blank.', __METHOD__ );
    
    $exceptions = array();

    // execute for every selected user
    foreach( $this->get_argument( 'user_id_list' ) as $user_id )
    {
      $this->get_record()->user_id = $user_id;
      try
      {
        parent::execute();
      }
      catch( \sabretooth\exception\base_exception $e )
      {
        $exceptions[] = $e;
      }

      // create a new shift record for the next iteration
      $this->set_record( new \sabretooth\database\shift() );
    }

    // throw an exception if any were caught
    if( 1 == count( $exceptions ) )
    {
      // test the exception type to decide what type of exception to throw
      $e = current( $exceptions );
      throw RUNTIME_SHIFT__SAVE_ERROR_NUMBER == $e->get_number() ?
        new \sabretooth\exception\notice( $e, __METHOD__, $e ) : $e;
    }
    else if( 1 < count( $exceptions ) )
    {
      $message = "The following errors have occured:<br>\n";
      foreach( $exceptions as $e )
      {
        // if we find an unexpected exception throw it instead of a notice
        if( RUNTIME_SHIFT__SAVE_ERROR_NUMBER != $e->get_number() ) throw $e;
        $message .= $e->get_raw_message()."<br>\n";
      }
      throw new \sabretooth\exception\notice( $message, __METHOD__ );
    }
  }
}
?>
