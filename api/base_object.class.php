<?php
/**
 * base_object.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 */

namespace sabretooth;

/**
 * base_object: master base object
 *
 * The base_object class from which all other sabretooth classes extend
 * @package sabretooth
 */
abstract class base_object
{
  /**
   * Returns the name of the class without namespaces
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @return string
   * @static
   * @access protected
   */
  protected static function get_class_name()
  {
    return substr( strrchr( get_called_class(), '\\' ), 1 );
  }
}
?>
