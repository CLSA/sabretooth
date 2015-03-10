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
require_once $SETTINGS['path']['CENOZO'].'/app/application.class.php';
$application = new \cenozo\application();
$application->execute();
?>
<!doctype html>
<html lang="en" ng-app="sabretoothApp">
<head>
  <meta charset="utf-8">
  <title>Sabretooth</title>
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/bower_components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/bower_components/angular-snap/angular-snap.min.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/css/app.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/css/animations.css">

  <script>window.cenozoUrl = "<?php print CENOZO_URL; ?>";</script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/jquery/dist/jquery.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/snapjs/snap.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular/angular.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-bootstrap/ui-bootstrap.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-bootstrap/ui-bootstrap-tpls.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-animate/angular-animate.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-route/angular-route.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-snap/angular-snap.min.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/app.js"></script>
  <script src="app/app.js"></script>

  <script src="<?php print CENOZO_URL; ?>/app/cenozo/animations.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/cenozo/controllers.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/cenozo/directives.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/cenozo/filters.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/cenozo/services.js"></script>

  <script src="<?php print CENOZO_URL; ?>/app/site/controllers.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/site/directives.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/site/services.js"></script>
  
  <script src="<?php print CENOZO_URL; ?>/app/user/controllers.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/user/directives.js"></script>
  <script src="<?php print CENOZO_URL; ?>/app/user/services.js"></script>
</head>
<body ng-controller="MainCtrl">

  <snap-drawer>
    <div class="btn-group-vertical full-width" role="group">
      <button class="btn btn-default"
              ng-repeat="menu in menuList"
              ng-class="{ 'btn-primary':current == menu}"
              ng-click="load( menu )"
              snap-close>{{ menu | uppercase }}S</button>
    </div>
  </snap-drawer>

  <snap-content snap-opt-disable="'right'" snap-opt-tap-to-close="true" snap-opt-min-drag-distance="10000">
    <button snap-toggle class="btn btn-primary menu-button">Menu</button>
    <div class="container outer-container" data-snap-ignore="true">
      <div ng-view class="container view-frame"></div>
      <div custom-drag-area></div>
    </div>
  </snap-content>


</body>
</html>
