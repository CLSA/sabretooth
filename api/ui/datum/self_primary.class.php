<?php
/**
 * self_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\datum;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * datum self primary
 * 
 * @package sabretooth\ui
 */
class self_primary extends \sabretooth\ui\datum
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
    parent::__construct( 'self', 'primary', $args );
  }

  /**
   * Returns the data provided by this datum operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access public
   */
  public function get_data()
  {
    $db_user = bus\session::self()->get_user();
    $data = array();
    foreach( $db_user->get_column_names() as $column ) $data[ $column ] = $db_user->$column;
    return $data;
  }

  /**
   * Primary data is always returned in json format
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @access public
   */
  public function get_data_type() { return "json"; }
}
?>
