<?php
/**
 * setting_manager.class.php
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 * @filesource
 */

namespace sabretooth\business;
use cenozo\lib, cenozo\log, sabretooth\util;

/**
 * Manages software settings
 */
class setting_manager extends \cenozo\business\setting_manager
{
  /**
   * Constructor.
   * 
   * @author Patrick Emond <emondpd@mcmaster.ca>
   * @throws exception\argument
   * @access protected
   */
  protected function __construct( $arguments )
  {
    parent::__construct( $arguments );

    $static_settings = $arguments[0];

    // add a few categories to the manager
    foreach( array( 'ivr', 'voip' ) as $category )
    {
      // make sure the category exists
      if( !array_key_exists( $category, $static_settings ) )
        throw lib::create( 'exception\argument',
          'static_settings['.$category.']', NULL, __METHOD__ );
      
      $this->static_settings[$category] = $static_settings[$category];
    }

    // get the survey database settings from the limesurvey config file
    $file = LIMESURVEY_PATH.'/config.php';
    if( file_exists( $file ) )
    {
      include $file;
      $this->static_settings['survey_db'] =
        array( 'driver' => $databasetype,
               'server' => $databaselocation,
               'username' => $databaseuser,
               'password' => $databasepass,
               'database' => $databasename,
               'prefix' => $dbprefix );
    }
    else // no version 1.92 of the config file, try version 2.0
    {
      $file = LIMESURVEY_PATH.'/application/config/config.php';

      if( file_exists( $file ) )
      {
        define( 'BASEPATH', '' ); // needed to read the config file
        $config = require( $file );
        $db = explode( ';', $config['components']['db']['connectionString'] );

        $parts = explode( ':', $db[0], 2 );
        $driver = current( $parts );
        $parts = explode( '=', $db[0], 2 );
        $server = next( $parts );
        $parts = explode( '=', $db[2], 2 );
        $database = next( $parts );

        $this->static_settings['survey_db'] =
          array( 'driver' => $driver,
                 'server' => $server,
                 'username' => $config['components']['db']['username'],
                 'password' => $config['components']['db']['password'],
                 'database' => $database,
                 'prefix' => $config['components']['db']['tablePrefix'] );
      }
      else throw lib::create( 'exception\runtime',
        'Cannot find limesurvey config.php file.', __METHOD__ );
    }
  }
}
