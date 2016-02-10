<?php
/**
 * Main web script which drives the application web api
 */

// load web-script common code
require_once '../../settings.ini.php';
require_once '../../settings.local.ini.php';
require_once $SETTINGS['path']['CENOZO'].'/src/bootstrap.class.php';
$bootstrap = new \cenozo\bootstrap();
$bootstrap->initialize( 'api' );
