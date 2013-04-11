<?php
/**
 * shift_template_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: shift_template primary
 */
class shift_template_primary extends \cenozo\ui\pull\base_primary
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Pull arguments.
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
  protected function finish()
  {
    parent::finish();

    if( '1' == util::get_datetime_object()->format( 'I' ) )
    {
      $start_datetime_obj = util::get_datetime_object( $this->data['start_time'] );
      $start_datetime_obj->sub( new \DateInterval( 'PT1H' ) );
      $this->data['start_time'] = $start_datetime_obj->format( 'H:i:s' );
      $end_datetime_obj = util::get_datetime_object( $this->data['end_time'] );
      $end_datetime_obj->sub( new \DateInterval( 'PT1H' ) );
      $this->data['end_time'] = $end_datetime_obj->format( 'H:i:s' );
    }
  }
}
?>
