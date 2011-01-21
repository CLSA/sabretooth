<?php
/**
 * singleton.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @package sabretooth
 */

namespace sabretooth;

/**
 * singleton: a class that only allows for a single, static object
 * 
 * This is an object factory that creates singletons.  Any class that extends this base class can
 * be instantiated by calling the {@link self} method.  That method will return one and only
 * one instance per class.
 * @package sabretooth
 */
abstract class singleton
{
  /**
   * Get the singleton of the class.
   * 
   * Call this method to get a reference to the one and only static object for the current class.
   * The first time this method is called for each child class this method creates and stores an
   * instance of the singleton.
   * If the constructor of a singleton class requires arguments then pass them to this method,
   * if not then do not pass arguments (doing so will be caught by the child class' constructor
   * as a logic/fatal error).
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @param mixed $arg1 used by the parent constructor, if needed
   * @param mixed $arg2 used by the parent constructor, if needed
   * @param mixed $arg3 used by the parent constructor, if needed, etc...
   * @return object
   * @access public
   */
  public static function self()
  {
    if( !self::exists() )
    {
      // if any arguments were passed to this method, pass them on to the contructor
      if( 0 < func_num_args() )
      {
        // this creates a child-class instance (new static = new child_class)
        self::$instance_list[ get_called_class() ] = new static( func_get_args() );
      }
      else
      {
        // this creates a child-class instance (new static = new child_class)
        self::$instance_list[ get_called_class() ] = new static();
      }
    }
    return self::$instance_list[ get_called_class() ];
  }
  
  public static function exists()
  {
    return isset( self::$instance_list[ get_called_class() ] );
  }

  /**
   * The static array of all singletons.
   * @var mixed
   * @access private
   */
  private static $instance_list = array();
}
?>
