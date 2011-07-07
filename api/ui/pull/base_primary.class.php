<?php
/**
 * base_primary.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth\ui
 * @filesource
 */

namespace sabretooth\ui\pull;
use sabretooth\log, sabretooth\util;
use sabretooth\business as bus;
use sabretooth\database as db;
use sabretooth\exception as exc;

/**
 * Base class for pull operations which provide a record's primary information.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_primary extends base_record_pull
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param string $subject The subject to retrieve the primary information from.
   * @param array $args Pull arguments.
   * @access public
   */
  public function __construct( $subject, $args )
  {
    parent::__construct( $subject, 'primary', $args );
    
    // make sure we have an id (we don't actually need to use it since the parent does)
    $this->get_argument( 'id' );
  }

  /**
   * Returns the data provided by this pull operation.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return associative array
   * @access public
   */
  public function finish()
  {
    $data = array();
    foreach( $this->get_record()->get_column_names() as $column )
      $data[ $column ] = $this->get_record()->$column;
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
