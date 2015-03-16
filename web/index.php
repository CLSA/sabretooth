<?php
/**
 * index.php
 * 
 * Main web script which drives the application.
 * 
 * @author Patrick Emond <emondpd@mcmaster.ca>
 */

namespace sabretooth;
use cenozo\lib, cenozo\log, sabretooth\util;

// load web-script common code
require_once '../settings.ini.php';
require_once '../settings.local.ini.php';
require_once $SETTINGS['path']['CENOZO'].'/api/bootstrap.class.php';
$bootstrap = new \cenozo\bootstrap();
$bootstrap->initialize( 'ui' );
