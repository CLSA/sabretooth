<?php
/**
 * factory.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 * @filesource
 */

namespace sabretooth;

/**
 * factory: a class that only allows for a single, static object for every construtor's argument
 *          value.
 * 
 * This is an object factory that creates multiple singletons.  Any class that extends this base
 * class can be instantiated by calling the {@link self} method.  That method will return one and
 * only one instance per class based on the first argument sent to {@link self}.
 * @package sabretooth
 */
abstract class factory
{
  /**
   * Get the factory of the class.
   * 
   * Call this method to get a reference to the one and only static object for the current class
   * and first given argument.
   * The first time this method is called for each child class the first argument is used to create
   * an instance of the parent class and stores it.
   * All arguments will be passed to the constructor, but the first argument is special in that it
   * must be of a native type so that it can be used to identify the singleton.
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param native $arg1 An argument which identifies the singleton.
                        MUST be provided and MUST be native (or NULL)!
   * @param mixed $arg2 used by the parent constructor, if needed
   * @param mixed $arg3 used by the parent constructor, if needed, etc...
   * @return object
   * @throws exception\runtime
   * @access public
   */
  public static function self()
  {
    // make sure there is at least one argument and it is a native type
    if( 1 > func_num_args() )
      throw new exception\runtime(
        'Tried to call self() method without at least one argument.', __METHOD__ );
    
    $arguments = func_get_args();
    if( !self::exists( $arguments[0] ) )
    {
      // this creates a child-class instance (new static = new child_class)
      self::$instance_list[ get_called_class() ][ serialize( $arguments[0] ) ] =
        new static( func_get_args() );
    }
    return self::$instance_list[ get_called_class() ][ serialize( $arguments[0] ) ];
  }
  
  public static function exists( $arg )
  {
    $type = gettype( $arg );
    if( 'boolean' != $type &&
        'integer' != $type &&
        'double' != $type &&
        'string' != $type &&
        'NULL' != $type )
      throw new exception\runtime(
        sprintf( 'Tried to use an incorrect argument type "%s" to identify factory '.
                 '(must be one of boolean, integer, double, string or NULL).',
                 $type ), __METHOD__ );
    
    return isset( self::$instance_list[ get_called_class() ][ serialize( $arg ) ]  );
  }

  /**
   * The static array of all factorys.
   * @var mixed
   * @access private
   */
  private static $instance_list = array();
}
?>
