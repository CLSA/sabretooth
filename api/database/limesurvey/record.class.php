<?php
/**
 * record.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\database\limesurvey;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * This is the abstract database table object for all limesurvey tables.
 */
abstract class record extends \cenozo\database\record
{
  /**
   * Constructor
   * 
   * See parent class's constructor.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param integer $id The primary key for this object.
   * @access public
   */
  public function __construct( $id = NULL )
  {
    parent::__construct( $id );
    $this->include_timestamps = false;
  }

  /**
   * Magic call method.
   * 
   * Disables the parent method so that it is compatible with limesurvey tables.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\runtime
   * @access public
   */
  public function __call( $name, $args )
  {
    throw lib::create( 'exception\runtime',
      sprintf( 'Call to undefined function: %s::%s().',
               get_called_class(),
               $name ), __METHOD__ );
  }

  /**
   * Returns the record's database.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return database
   * @static
   * @access protected
   */
  public static function db()
  {
    return lib::create( 'business\session' )->get_survey_database();
  }
}
