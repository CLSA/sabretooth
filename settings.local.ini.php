<?php
/**
 * settings.local.ini.php
 * 
 * Defines local initialization settings for sabretooth, overriding default settings found in
 * settings.ini.php
 */

global $SETTINGS;

// whether or not to run the application in development mode
$SETTINGS['general']['development_mode'] = true;

// defines the username and password used by mastodon when communicating as a machine
$SETTINGS['general']['machine_user'] = 'mastodon';
$SETTINGS['general']['machine_password'] = '1qaz2wsx';

// the file path to the framework and application
$SETTINGS['path']['CENOZO'] = '/home/patrick/files/repositories/cenozo';
$SETTINGS['path']['APPLICATION'] = '/home/patrick/files/repositories/sabretooth';

// the path to the log file
$SETTINGS['path']['LOG_FILE'] = $SETTINGS['path']['APPLICATION'].'/log';

// the url of Mastodon (cannot be relative)
$SETTINGS['url']['MASTODON'] = 'https://localhost/patrick/mastodon';

// database settings (the driver, server and prefixes are set in the framework's settings)
$SETTINGS['db']['username'] = 'patrick';
$SETTINGS['db']['password'] = '1qaz2wsx';

// IVR setup
$SETTINGS['ivr']['enabled'] = true;
$SETTINGS['ivr']['host'] = 'https://www.vocantasonline.com';
$SETTINGS['ivr']['service'] = '/McqWS/ParticipantOperations.asmx?wsdl';
$SETTINGS['ivr']['username'] = 'McqUser';
$SETTINGS['ivr']['password'] = 'm3!Cl$A1';
