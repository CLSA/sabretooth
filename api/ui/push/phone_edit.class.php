<?php
/**
 * phone_edit.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\push;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * push: phone edit
 *
 * Edit a phone.
 * @package sabretooth\ui
 */
class phone_edit extends base_edit
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Push arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'phone', $args );
  }

  // TODO: document
  protected function prepare()
  {
    parent::prepare();

    $this->set_machine_request_enabled( true );
    $this->set_machine_request_url( MASTODON_URL );
  }

  protected function validate()
  {
    parent::validate();

    $columns = $this->get_argument( 'columns' );

    // if there is a phone number, validate it
    if( array_key_exists( 'number', $columns ) )
    {
      // validate the phone number
      $number_only = preg_replace( '/[^0-9]/', '', $columns['number'] );
      if( 10 != strlen( $number_only ) )
        throw lib::create( 'exception\notice',
          'Phone numbers must have exactly 10 digits.', __METHOD__ );

      $formatted_number = sprintf( '%s-%s-%s',
                                   substr( $number_only, 0, 3 ),
                                   substr( $number_only, 3, 3 ),
                                   substr( $number_only, 6 ) );
      if( !util::validate_phone_number( $formatted_number ) )
        throw lib::create( 'exception\notice',
          sprintf( 'The provided number "%s" is not a valid North American phone number.',
                   $formatted_number ),
          __METHOD__ );
    }
  }
}
?>
