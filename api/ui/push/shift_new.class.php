<?php
/**
 * shift_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: shift new
 *
 * Create a new shift.
 */
class shift_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    $columns = $this->get_argument( 'columns' );

    // make sure the date column isn't blank
    if( !array_key_exists( 'date', $columns ) || 0 == strlen( $columns['date'] ) )
      throw lib::create( 'exception\notice', 'The date cannot be left blank.', __METHOD__ );
  }

  /**
   * Sets up the operation with any pre-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    $columns = $this->get_argument( 'columns', array() );

    // the UI provides date, start time and end time, need to convert to start_datetime
    // and end_datetime
    $this->arguments['columns']['start_datetime'] = $columns['date'].' '.$columns['start_time'];
    $this->arguments['columns']['end_datetime'] = $columns['date'].' '.$columns['end_time'];
    unset( $this->arguments['columns']['date'] );
    unset( $this->arguments['columns']['start_time'] );
    unset( $this->arguments['columns']['end_time'] );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    $exceptions = array();

    // execute for every selected user
    foreach( $this->get_argument( 'user_id_list' ) as $user_id )
    {
      $this->get_record()->user_id = $user_id;
      try { parent::execute(); }
      catch( \cenozo\exception\base_exception $e ) { $exceptions[] = $e; }

      // create a new shift record for the next iteration
      $this->set_record( lib::create( 'database\shift' ) );
    }

    // throw an exception if any were caught
    if( 1 == count( $exceptions ) )
    {
      // test the exception type to decide what type of exception to throw
      $e = current( $exceptions );
      throw RUNTIME__SABRETOOTH_DATABASE_SHIFT__SAVE__ERRNO == $e->get_number() ?
        lib::create( 'exception\notice', $e, __METHOD__, $e ) : $e;
    }
    else if( 1 < count( $exceptions ) )
    {
      $message = "The following errors have occured:<br>\n";
      foreach( $exceptions as $e )
      {
        // if we find an unexpected exception throw it instead of a notice
        if( RUNTIME__SABRETOOTH_DATABASE_SHIFT__SAVE__ERRNO != $e->get_number() ) throw $e;
        $message .= $e->get_raw_message()."<br>\n";
      }
      throw lib::create( 'exception\notice', $message, __METHOD__ );
    }
  }
}
?>
