<?php
/**
 * participant_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;

/**
 * datum participant primary
 * 
 * @package sabretooth\ui
 */
class participant_primary extends base_primary
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $args )
  {
    // if the id is "assignment", then fetch the participant id based on the current assignment
    if( isset( $args['id'] ) && 'assignment' == $args['id'] )
    {
      $db_assignment = \sabretooth\business\session::self()->get_current_assignment();
      if( is_null( $db_assignment ) )
        throw new \sabretooth\exception\runtime(
          'Cannot get the current participant, there is no active assignment.', __METHOD__ );
      $args['id'] = $db_participant = $db_assignment->get_interview()->get_participant()->id;
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
  public function get_data()
  {
    $data = parent::get_data();

    // add the primary address
    $db_contact = $this->get_record()->get_primary_location();
    if( !is_null( $db_contact ) )
    {
      $data['street'] = is_null( $db_contact->address2 )
                      ? $db_contact->address1
                      : $db_contact->address1.', '.$db_contact->address2;
      $data['city'] = $db_contact->city;
      $data['province'] = $db_contact->get_province()->name;
      $data['postcode'] = $db_contact->postcode;
    }

    return $data;
  }
}
?>
