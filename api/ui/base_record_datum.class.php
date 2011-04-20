<?php
/**
 * base_record_datum.class.php
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
 * Base class for all datum pertaining to a single record.
 * 
 * @abstract
 * @package sabretooth\ui
 */
abstract class base_record_datum extends datum implements contains_record
{
  /**
   * Constructor
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param array $args An associative array of arguments to be processed by the datum
   * @access public
   */
  public function __construct( $subject, $name, $args )
  {
    parent::__construct( $subject, $name, $args );

    $class_name = '\\sabretooth\\database\\'.$this->get_subject();
    $this->set_record( new $class_name( $this->get_argument( 'id', NULL ) ) );
  }
  
  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @return database\record
   * @access public
   */
  public function get_record()
  {
    return $this->record;
  }

  /**
   * Method required by the contains_record interface.
   * @author Patrick Emond
   * @param database\record $record
   * @access public
   */
  public function set_record( $record )
  {
    $this->record = $record;
  }

  /**
   * An record of the item being viewed.
   * @var record
   * @access private
   */
  private $record = NULL;
}
?>
