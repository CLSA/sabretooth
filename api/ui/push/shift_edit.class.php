<?php
/**
 * shift_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: shift edit
 *
 * Edit a shift.
 */
class shift_edit extends \cenozo\ui\push\base_edit
{
  /**
   * Constructor.
   * @autho Patrick Emond <emondpd@mcmaster.ca>
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

    // the UI provides date, start time and end time, need to convert to start_datetime
    // and end_datetime
    $columns = $this->get_argument( 'columns', array() );
    
    $date = array_key_exists( 'date', $columns )
          ? $columns['date']
          : substr( $this->get_record()->start_datetime, 0, 10 );
    $start_time = array_key_exists( 'start_time', $columns )
                ? $columns['start_time']
                : substr( $this->get_record()->start_datetime, 11, -3 );
    $end_time = array_key_exists( 'end_time', $columns )
              ? $columns['end_time']
              : substr( $this->get_record()->end_datetime, 11, -3 );

    $this->arguments['columns']['start_datetime'] = $date.' '.$start_time;
    $this->arguments['columns']['end_datetime'] = $date.' '.$end_time;
    unset( $this->arguments['columns']['date'] );
    unset( $this->arguments['columns']['start_time'] );
    unset( $this->arguments['columns']['end_time'] );
  }
}
?>
