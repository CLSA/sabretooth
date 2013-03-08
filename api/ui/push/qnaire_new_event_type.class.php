<?php
/**
 * qnaire_new_event_type.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: qnaire new_event_type
 */
class qnaire_new_event_type extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @event_type public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'new_event_type', $args );
  }

  /**
   * This method executes the operation's purpose.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function execute()
  {
    parent::execute();

    $this->get_record()->add_event_type( $this->get_argument( 'id_list' ) );
  }
}
