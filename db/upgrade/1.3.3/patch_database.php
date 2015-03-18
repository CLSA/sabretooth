#!/usr/bin/php
<?php
/**
 * This is a special script used when upgrading to version 1.3.3
 * This script should be run once either before or after running patch_database.sql
 * It loads all recordings found in the MONITOR directory into the recording table
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

ini_set( 'display_errors', '1' );
error_reporting( E_ALL | E_STRICT );
ini_set( 'date.timezone', 'US/Eastern' );

// utility functions
function out( $msg ) { printf( '%s: %s'."\n", date( 'Y-m-d H:i:s' ), $msg ); }
function error( $msg ) { out( sprintf( 'ERROR! %s', $msg ) ); }

class patch
{
  public function add_settings( $settings, $replace = false )
  {
    if( $replace )
    {
      $this->settings = $settings;
    }
    else
    {
      foreach( $settings as $category => $setting )
      {
        if( !array_key_exists( $category, $this->settings ) )
        {
          $this->settings[$category] = $setting;
        }
        else
        {
          foreach( $setting as $key => $value )
            if( !array_key_exists( $key, $this->settings[$category] ) )
              $this->settings[$category][$key] = $value;
        }
      }
    }
  }

  public function execute()
  {
    $error_count = 0;
    $file_count = 0;

    out( 'Reading configuration parameters' );
    // fake server parameters
    $_SERVER['HTTPS'] = false;
    $_SERVER['HTTP_HOST'] = 'localhost';

    require_once '../../../web/settings.ini.php';
    require_once '../../../web/settings.local.ini.php';

    // include the application's initialization settings
    global $SETTINGS;
    $this->add_settings( $SETTINGS, true );
    unset( $SETTINGS );

    // include the framework's initialization settings
    require_once $this->settings['path']['CENOZO'].'/app/settings.local.ini.php';
    $this->add_settings( $settings );
    require_once $this->settings['path']['CENOZO'].'/app/settings.ini.php';
    $this->add_settings( $settings );

    if( !array_key_exists( 'general', $this->settings ) ||
        !array_key_exists( 'application_name', $this->settings['general'] ) )
      die( 'Error, application name not set!' );

    define( 'APPNAME', $this->settings['general']['application_name'] );
    define( 'SERVICENAME', $this->settings['general']['service_name'] );
    $this->settings['path']['CENOZO_API'] = $this->settings['path']['CENOZO'].'/api';
    $this->settings['path']['CENOZO_TPL'] = $this->settings['path']['CENOZO'].'/tpl';

    $this->settings['path']['API'] = $this->settings['path']['APPLICATION'].'/api';
    $this->settings['path']['DOC'] = $this->settings['path']['APPLICATION'].'/doc';
    $this->settings['path']['TPL'] = $this->settings['path']['APPLICATION'].'/tpl';

    // the web directory cannot be extended
    $this->settings['path']['WEB'] = $this->settings['path']['CENOZO'].'/web';

    foreach( $this->settings['path'] as $path_name => $path_value )
      define( $path_name.'_PATH', $path_value );
    foreach( $this->settings['url'] as $path_name => $path_value )
      define( $path_name.'_URL', $path_value );

    // open connection to the database
    out( 'Connecting to database' );
    require_once $this->settings['path']['ADODB'].'/adodb.inc.php';
    $db = ADONewConnection( $this->settings['db']['driver'] );
    $db->SetFetchMode( ADODB_FETCH_ASSOC );
    $database = sprintf( '%s%s',
                         $this->settings['db']['database_prefix'],
                         $this->settings['general']['application_name'] );
                        
    $result = $db->Connect( $this->settings['db']['server'],
                            $this->settings['db']['username'],
                            $this->settings['db']['password'],
                            $database );
    if( false == $result )
    {
      error( 'Unable to connect, quiting' );
      die();
    }

    // Get a list of all *out* recordings in the monitor directory
    out( 'Reading a list of all recordings in the monitor directory' );
    $glob_search = sprintf( '%s/*/*/*_*-*-out.wav', VOIP_MONITOR_PATH );
    $recording_list = array();
    foreach( glob( $glob_search ) as $filename )
    {
      // remove the path from the filename
      $parts = preg_split( '#/#', $filename );
      $total = count( $parts );
      if( 3 <= $total )
      {
        $interview1 = $parts[$total-3];
        $interview2 = $parts[$total-2];

        // get the (rest of the) interview and assignment id from the filename
        $parts = preg_split( '/[-_]/', end( $parts ) );
        if( 3 <= count( $parts ) )
        {
          $recording_list[] = array(
            'interview_id' => (int) sprintf( '%s%s%s', $interview1, $interview2, $parts[0] ),
            'assignment_id' => 0 < $parts[1] ? (int) $parts[1] : 'NULL',
            'rank' => (int)$parts[2] );
        }
      }
    }

    $total = count( $recording_list );
    out( sprintf( 'Updating recording table with recordings found on disk', $total ) );

    $values = '';
    $first = true;
    foreach( $recording_list as $index => $recording )
    {
      $values .= sprintf( '%s(%d,%s,%d)',
                          $first ? '' : ', ',
                          $recording['interview_id'],
                          $recording['assignment_id'],
                          $recording['rank'] );
      $first = false;

      if( 999 == $index % 1000 )
      {
        $sql = sprintf(
          'INSERT IGNORE INTO recording(interview_id,assignment_id,rank) VALUES %s',
          $values );
        if( false === $db->Execute( $sql ) )
          error( sprintf( 'Failed to insert records, server responded with "%s"', $db->ErrorMsg() ) );
        $first = true;
        $values = '';

        out( sprintf(
          'Processed %d of %d recordings (%d rows affected)',
          $index + 1,
          $total,
          $db->Affected_Rows() ) );
      }
    }

    // insert the last set
    if( 0 < strlen( $values ) )
    {
      $sql = sprintf(
        'INSERT IGNORE INTO recording(interview_id,assignment_id,rank) VALUES %s',
        $values );
      if( false === $db->Execute( $sql ) )
          error( sprintf( 'Failed to insert records, server responded with "%s"', $db->ErrorMsg() ) );

      out( sprintf(
        'Processed %d of %d recordings (%d rows affected)',
        $index + 1,
        $total,
        $db->Affected_Rows() ) );
    }
  }
}

$patch = new patch();
$patch->execute();
