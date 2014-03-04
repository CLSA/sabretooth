<?php
/**
 * settings.ini.php
 * 
 * Defines initialization settings for sabretooth.
 * DO NOT edit this file, to override these settings use settings.local.ini.php instead.
 * Any changes in the local ini file will override the settings found here.
 */

global $SETTINGS;

// tagged version
$SETTINGS['general']['application_name'] = 'sabretooth';
$SETTINGS['general']['service_name'] = $SETTINGS['general']['application_name'];
$SETTINGS['general']['version'] = '1.3.2';

// always leave as false when running as production server
$SETTINGS['general']['development_mode'] = false;

// the name of the cohort associated with this application
$SETTINGS['general']['cohort'] = 'default';

// the location of sabretooth internal path
$SETTINGS['path']['APPLICATION'] = '/usr/local/lib/sabretooth';

// the location of the Shift8 Asterisk library
$SETTINGS['path']['SHIFT8'] = '/usr/local/lib/shift8';

// the url to Mastodon (set to NULL to disable Mastodon support)
$SETTINGS['url']['MASTODON'] = NULL;

// the url of limesurvey
$SETTINGS['path']['LIMESURVEY'] = '/var/www/limesurvey';
$SETTINGS['url']['LIMESURVEY'] = '../limesurvey';

// additional javascript libraries
$SETTINGS['url']['JQUERY'] = '/jquery';
$SETTINGS['url']['JQUERY_PLUGINS'] = $SETTINGS['url']['JQUERY'].'/plugins';
$SETTINGS['url']['JQUERY_TIMERS_JS'] = $SETTINGS['url']['JQUERY_PLUGINS'].'/timers.js';

// voip settings
$SETTINGS['voip']['enabled'] = false;
$SETTINGS['voip']['url'] = 'http://localhost:8088/mxml';
$SETTINGS['voip']['username'] = '';
$SETTINGS['voip']['password'] = '';
$SETTINGS['voip']['prefix'] = '';
$SETTINGS['voip']['xor_key'] = '';

// the directory to write recorded calls
// (must be an absolute path that the asterisk server's user has access to)
$SETTINGS['path']['VOIP_MONITOR'] = '/var/local/sabretooth/monitor';
