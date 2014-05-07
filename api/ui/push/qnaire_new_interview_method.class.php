<?php
/**
 * qnaire_new_interview_method.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: qnaire new_interview_method
 */
class qnaire_new_interview_method extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @interview_method public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'new_interview_method', $args );
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

    $this->get_record()->add_interview_method( $this->get_argument( 'id_list' ) );
  }
}
