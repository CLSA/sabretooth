#!/usr/bin/php
<?php
/**
 * This file will search through the code to find all methods which throw exceptions.
 * It uses this information to rebuild the api/exception/error_codes.inc.php file.
 */

function print_exception_block( $type, $list )
{
  // first find the longest line
  $max_length = 0;
  foreach( $list as $class_name => $method_list )
  {
    foreach( $method_list as $method_name )
    {
      $length = strlen( sprintf( "define( '%s_%s__%s_ERROR_NUMBER',",
                                 strtoupper( $type ),
                                 strtoupper( $class_name ),
                                 strtoupper( $method_name ) ) );
      if( $length > $max_length ) $max_length = $length;
    }
  }

  // now print out the lines
  $counter = 1;
  ksort( $list );
  foreach( $list as $class_name => $method_list )
  {
    ksort( $method_list );
    $method_list = array_unique( $method_list );
    foreach( $method_list as $method_name )
    {
      // add the first part
      $string = sprintf( "define( '%s_%s__%s_ERROR_NUMBER',",
                         strtoupper( $type ),
                         strtoupper( $class_name ),
                         strtoupper( $method_name ) );
      
      // pad spaces up to the max
      $string = str_pad( $string, $max_length );

      // now print the whole line
      printf( "%s %s_BASE_ERROR_NUMBER + %d );\n",
              $string,
              strtoupper( $type ),
              $counter++ );
    }
  }
}

// if we are in the aux/ directory then back out
if( preg_match( '#/aux$#', getcwd() ) ) chdir( '..' );

// grep for all method declarations and new exceptions in the api/ directory
$return_status = -1;
$grep_line_list = array();
exec( 'grep -Hrn "\(^ *\(public\|private\|protected\)\( static\)\? function\)\|\(new exc\)" api/*',
      $grep_line_list, $return_status );

if( 0 != $return_status ) die( 'There was an error when fetching method list.' );

$error_codes = array();
$current_class_name = NULL;
$current_method_name = NULL;

foreach( $grep_line_list as $grep_line )
{
  if( preg_match( '#new exc#', $grep_line ) )
  { // this line is a new exception
    // make sure we have a class and method name
    if( is_null( $current_class_name ) || is_null( $current_method_name ) )
      die( 'An exception was found without knowing the '.
           'class and/or method name that it belongs to.' );

    // get the exception type
    $start_match = 'exc\\';
    $start = strpos( $grep_line, $start_match );
    if( false === $start ) $start_match = 'exception\\';
    $start = strpos( $grep_line, $start_match );
    if( false === $start ) continue;
    $start += strlen( $start_match );

    $end_match = '(';
    $end = strpos( $grep_line, $end_match, $start );
    
    // make sure a match was found
    if( false === $start || false === $end ) continue;
    $exception_type = substr( $grep_line, $start, $end - $start );
    
    // now add the error code
    if( !array_key_exists( $exception_type, $error_codes ) )
      $error_codes[$exception_type] = array();
    if( !array_key_exists( $current_class_name, $error_codes[$exception_type] ) )
      $error_codes[$exception_type][$current_class_name] = array();
    $error_codes[$exception_type][$current_class_name][] = $current_method_name;
  }
  else
  { // this line is a new method
    // get the class name

    // find the first / before the first :
    $colon_position = strpos( $grep_line, ':' );
    if( false === $colon_position ) continue;
    $start_match = '/';
    $end_match = '.class';
    $start = strrpos( substr( $grep_line, 0, $colon_position ), $start_match ) +
             strlen( $start_match );
    $end = strpos( $grep_line, $end_match, $start );
    
    // make sure a match was found
    if( false === $start || false === $end ) continue;
    $class_name = substr( $grep_line, $start, $end - $start );

    // get the method name
    $start_match = 'function ';
    $end_match = '(';
    $start = strpos( $grep_line, $start_match ) + strlen( $start_match );
    $end = strpos( $grep_line, $end_match, $start );

    // make sure a match was found
    if( false === $start || false === $end ) continue;
    $method_name = substr( $grep_line, $start, $end - $start );
    
    $current_class_name = $class_name;
    $current_method_name = $method_name;
  }
}

// now look in the web directory for exceptions thrown outside of a class
$grep_line_list = array();
exec( 'grep -Hrn "new exc" web/*',
      $grep_line_list, $return_status );

if( 0 != $return_status ) die( 'There was an error when fetching exception list.' );

foreach( $grep_line_list as $grep_line )
{
  // get the script name

  // find the first / before the first :
  $colon_position = strpos( $grep_line, ':' );
  if( false === $colon_position ) continue;
  $start_match = '/';
  $end_match = '.php';
  $start = strrpos( substr( $grep_line, 0, $colon_position ), $start_match ) +
           strlen( $start_match );
  $end = strpos( $grep_line, $end_match, $start );
  
  // make sure a match was found
  if( false === $start || false === $end ) continue;
  $script_name = substr( $grep_line, $start, $end - $start );
  
  // get the exception type
  $start_match = 'exc\\';
  $start = strpos( $grep_line, $start_match );
  if( false === $start ) $start_match = 'exception\\';
  $start = strpos( $grep_line, $start_match );
  if( false === $start ) continue;
  $start += strlen( $start_match );

  $end_match = '(';
  $end = strpos( $grep_line, $end_match, $start );
    
  // make sure a match was found
  if( false === $start || false === $end ) continue;
  $exception_type = substr( $grep_line, $start, $end - $start );

  // now add the error code
  if( !array_key_exists( $exception_type, $error_codes ) )
    $error_codes[$exception_type] = array();
  if( !array_key_exists( $script_name, $error_codes[$exception_type] ) )
    $error_codes[$exception_type][$script_name] = array();
  $error_codes[$exception_type][$script_name][] = 'script';
}

// now print out the file
print <<<OUTPUT
<?php
/**
 * error_codes.inc.php
 * 
 * This file is where all error codes are defined.
 * All error code are named after the class and function they occur in.
 * @package sabretooth\exception
 * @filesource
 */

namespace sabretooth\exception;

/**
 * Error number category defines.
 */
define( 'ARGUMENT_BASE_ERROR_NUMBER',   100000 );
define( 'DATABASE_BASE_ERROR_NUMBER',   200000 );
define( 'LDAP_BASE_ERROR_NUMBER',       300000 );
define( 'NOTICE_BASE_ERROR_NUMBER',     400000 );
define( 'PERMISSION_BASE_ERROR_NUMBER', 500000 );
define( 'RUNTIME_BASE_ERROR_NUMBER',    600000 );
define( 'SYSTEM_BASE_ERROR_NUMBER',     700000 );
define( 'TEMPLATE_BASE_ERROR_NUMBER',   800000 );
define( 'VOIP_BASE_ERROR_NUMBER',       900000 );

/**
 * "argument" error codes
 */

OUTPUT;

// now print all argument exceptions
print_exception_block( 'argument', $error_codes['argument'] );

print <<<OUTPUT

/**
 * "database" error codes
 * 
 * Since database errors already have codes this list is likely to stay empty.
 */

/**
 * "ldap" error codes
 * 
 * Since ldap errors already have codes this list is likely to stay empty.
 */

/**
 * "notice" error codes
 */

OUTPUT;

// now print all notice exceptions
print_exception_block( 'notice', $error_codes['notice'] );

print <<<OUTPUT

/**
 * "permission" error codes
 */

OUTPUT;

// now print all permission exceptions
print_exception_block( 'permission', $error_codes['permission'] );

print <<<OUTPUT

/**
 * "runtime" error codes
 */

OUTPUT;

// now print all runtime exceptions
print_exception_block( 'runtime', $error_codes['runtime'] );

print <<<OUTPUT

/**
 * "system" error codes
 * 
 * Since system errors already have codes this list is likely to stay empty.
 */

/**
 * "template" error codes
 * 
 * Since template errors already have codes this list is likely to stay empty.
 */

/**
 * "voip" error codes
 */

OUTPUT;

// now print all voip exceptions
print_exception_block( 'voip', $error_codes['voip'] );

print "\n?>\n";

?>
