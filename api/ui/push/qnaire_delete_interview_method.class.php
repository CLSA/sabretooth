<?php
/**
 * qnaire_delete_interview_method.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: qnaire delete_interview_method
 */
class qnaire_delete_interview_method extends \cenozo\ui\push\base_record
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'qnaire', 'delete_interview_method', $args );
  }

  /**
   * Validate the operation.  If validation fails this method will throw a notice exception.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws excpetion\argument, exception\permission
   * @access protected
   */
  protected function validate()
  {
    parent::validate();

    // make sure to not delete the last interview method
    if( 1 == $this->get_record()->get_interview_method_count() )
      throw lib::create( 'exception\notice',
        'Can\'t remove interview method, questionnaires must have at least one interview method.',
        __METHOD__ );
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

    $this->get_record()->remove_interview_method( $this->get_argument( 'remove_id' ) );
  }

  /**
   * Finishes the operation with any post-execution instructions that may be necessary.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access protected
   */
  protected function finish()
  {
    parent::finish();

    // change the default interview method if it is no longer available to this qnaire
    $record = $this->get_record();
    if( $this->get_argument( 'remove_id' ) == $record->default_interview_method_id )
    {
      $db_interview_method = current( $record->get_interview_method_list() );
      $record->default_interview_method_id = $db_interview_method->id;
      $record->save();
    }
  }
}
