<?php
/**
 * participant_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: participant primary
 */
class participant_primary extends \cenozo\ui\pull\base_primary
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
    parent::__construct( 'participant', $args );
  }

  /**
   * Processes arguments, preparing them for the operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\notice
   * @access protected
   */
  protected function prepare()
  {
    parent::prepare();

    // if the id is "assignment", then fetch the participant id based on the current assignment
    if( isset( $this->arguments['id'] ) && 'assignment' == $this->arguments['id'] )
    {
      $db_assignment = lib::create( 'business\session' )->get_current_assignment();
      if( is_null( $db_assignment ) )
        throw lib::create( 'exception\runtime',
          'Cannot get the current participant, there is no active assignment.', __METHOD__ );
      $this->arguments['id'] = $db_assignment->get_interview()->get_participant()->id;
    }
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

    // add the primary address
    $db_address = $this->get_record()->get_primary_address();
    if( !is_null( $db_address ) )
    {
      $this->data['street'] = is_null( $db_address->address2 )
                      ? $db_address->address1
                      : $db_address->address1.', '.$db_address->address2;
      $this->data['city'] = $db_address->city;
      $this->data['region'] = $db_address->get_region()->name;
      $this->data['postcode'] = $db_address->postcode;
    }
  }
}
?>
