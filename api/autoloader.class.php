<?php
namespace sabretooth;
class autoloader
{
  static public function register()
  {
    ini_set( 'unserialize_callback_func', 'spl_autoload_call' );
    spl_autoload_register( array( new self, 'autoload' ) );
  }

  static public function autoload( $class )
  {
    // only work on classes starting with sabretooth\
    if( 0 !== strpos( $class, 'sabretooth\\' ) ) return;

    // build the path based on the class' name and namespace
    $file = API_PATH.
      str_replace( '\\', '/', substr( $class, strpos( $class, '\\' ) ) ).'.class.php';
    if( file_exists( $file ) )
    {
      require $file;
      if( class_exists( 'sabretooth\log' ) ) log::singleton()->notice( 'autoloading: '.$class );
    }
    else
    {
      throw new exception\missing( $class );
    }
  }
}
