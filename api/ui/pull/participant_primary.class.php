<?php
/**
 * participant_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * pull: participant primary
 * 
 * @package sabretooth\ui
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
    // if the id is "assignment", then fetch the participant id based on the current assignment
    if( isset( $args['id'] ) && 'assignment' == $args['id'] )
    {
      $db_assignment = lib::create( 'business\session' )->get_current_assignment();
      if( is_null( $db_assignment ) )
        throw lib::create( 'exception\runtime',
          'Cannot get the current participant, there is no active assignment.', __METHOD__ );
      $args['id'] = $db_assignment->get_interview()->get_participant()->id;
    }

    parent::__construct( 'participant', $args );
  }

  /**
   * Overrides the parent class' base functionality by adding more data.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access public
   */
  public function finish()
  {
    $data = parent::finish();

    // add the primary address
    $db_address = $this->get_record()->get_primary_address();
    if( !is_null( $db_address ) )
    {
      $data['street'] = is_null( $db_address->address2 )
                      ? $db_address->address1
                      : $db_address->address1.', '.$db_address->address2;
      $data['city'] = $db_address->city;
      $data['region'] = $db_address->get_region()->name;
      $data['postcode'] = $db_address->postcode;
    }

    return $data;
  }
}
?>
