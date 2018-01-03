<?php
/**
 * Main web script which drives the application
 */

namespace sabretooth;
use cenozo\lib, cenozo\log, sabretooth\util;

if( !array_key_exists( 'REDIRECT_URL', $_SERVER ) ||
    0 == preg_match( '#/app/[^/]+/[^/]+.extend(\.min)?.js#', $_SERVER['REDIRECT_URL'] ) )
{
  // load web-script common code
  require_once '../settings.ini.php';
  require_once '../settings.local.ini.php';
  require_once $SETTINGS['path']['CENOZO'].'/src/bootstrap.class.php';
  $bootstrap = new \cenozo\bootstrap();
  $bootstrap->initialize( 'ui' );
}
