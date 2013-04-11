<?php
/**
 * shift_template_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: shift_template new
 *
 * Create a new shift_template.
 */
class shift_template_new extends \cenozo\ui\push\base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'shift_template', $args );
  }

  /**
   * If server is in daylight savings mode, convert to standard time
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function setup()
  {
    parent::setup();

    if( '1' == util::get_datetime_object()->format( 'I' ) )
    {
      $start_datetime_obj = util::get_datetime_object( $this->arguments['columns']['start_time'] );
      $start_datetime_obj->add( new \DateInterval( 'PT1H' ) );
      $this->arguments['columns']['start_time'] = $start_datetime_obj->format( 'H:i:s' );
      $end_datetime_obj = util::get_datetime_object( $this->arguments['columns']['end_time'] );
      $end_datetime_obj->add( new \DateInterval( 'PT1H' ) );
      $this->arguments['columns']['end_time'] = $end_datetime_obj->format( 'H:i:s' );
    }
  }
}
?>
