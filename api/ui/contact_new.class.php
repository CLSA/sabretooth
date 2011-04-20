<?php
/**
 * contact_new.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * action contact new
 *
 * Create a new contact.
 * @package sabretooth\ui
 */
class contact_new extends base_new
{
  /**
   * Constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args Action arguments
   * @access public
   */
  public function __construct( $args )
  {
    parent::__construct( 'contact', $args );
  }
  
  /**
   * Executes the action.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @access public
   */
  public function execute()
  {
    // make sure there is either a phone number OR city and province
    $columns = $this->get_argument( 'columns' );
    if( !( $columns['phone'] || ( $columns['province_id'] && $columns['city'] ) ) )
      throw new exc\notice(
        'You must provide a phone number OR city and province.', __METHOD__ );
    
    // if there is a phone number, validate it
    if( $columns['phone'] )
    {
      if( 10 != strlen( preg_replace( '/[^0-9]/', '', $columns['phone'] ) ) )
        throw new exc\notice(
          'Phone number must have exactly 10 digits.', __METHOD__ );
    }

    parent::execute();
  }
}
?>
