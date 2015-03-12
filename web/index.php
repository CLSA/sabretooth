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

function get_file_list( $path, $extension )
{
  $list = array();
  foreach( scandir( $path ) as $filename )
  {
    if( '.' != $filename && '..' != $filename )
    {
      $full_filename = $path.'/'.$filename;
      if( is_dir( $full_filename ) )
      {
        $list = array_merge( $list, get_file_list( $full_filename, $extension ) );
      }
      else
      {
        $pos = strrpos( $filename, '.' );
        if( false !== $pos && substr( $filename, $pos + 1 ) == $extension )
          $list[] = $full_filename;
      }
    }
  }

  return $list;
}

// get a list of all cenozo and application javascript files
$cenozo_js_list = get_file_list( CENOZO_PATH.'/web/app', 'js' );
foreach( $cenozo_js_list as $index => $filename )
  $cenozo_js_list[$index] = str_replace( CENOZO_PATH.'/web', CENOZO_URL, $filename );
$app_js_list = get_file_list( APPLICATION_PATH.'/web/app', 'js' );
foreach( $app_js_list as $index => $filename )
  $app_js_list[$index] = str_replace( APPLICATION_PATH.'/web/', '', $filename );
?>
<!doctype html>
<html lang="en" ng-app="sabretoothApp">
<head>
  <meta charset="utf-8">
  <title>Sabretooth</title>
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/bower_components/bootstrap/dist/css/bootstrap.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/bower_components/angular-snap/angular-snap.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/css/app.css">
  <link rel="stylesheet" href="<?php print CENOZO_URL; ?>/css/animations.css">

  <script>window.cenozoUrl = "<?php print CENOZO_URL; ?>";</script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/jquery/dist/jquery.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/bootstrap/dist/js/bootstrap.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/snapjs/snap.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular/angular.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-bootstrap/ui-bootstrap.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-bootstrap/ui-bootstrap-tpls.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-animate/angular-animate.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-route/angular-route.js"></script>
  <script src="<?php print CENOZO_URL; ?>/bower_components/angular-snap/angular-snap.js"></script>

<?php foreach( $cenozo_js_list as $filename ) printf( '<script src="%s"></script>'."\n", $filename ); ?>
<?php foreach( $app_js_list as $filename ) printf( '<script src="%s"></script>'."\n", $filename ); ?>
</head>
<body>

  <div class="span-drawers">
    <div class="snap-drawer snap-drawer-left" ng-controller="StMenuCtrl">
      <accordion close-others="true">
        <accordion-group ng-init="isOpen = true" is-open="isOpen">
          <accordion-heading>
            <button class="btn btn-primary btn-accordion full-width">Lists</button>
          </accordion-heading>
          <div class="btn-group-vertical full-width" role="group">
            <button class="btn btn-default"
                    ng-repeat="item in lists"
                    ng-class="{ 'btn-info': current == item.subject }"
                    ng-click="load( item.subject )"
                    snap-close>{{ item.title }}</button>
          </div>
        </accordion-group>
        <accordion-group>
          <accordion-heading>
            <button class="btn btn-primary btn-accordion full-width">Utilities</button>
          </accordion-heading>
          <div class="btn-group-vertical full-width" role="group">
            <button class="btn btn-default"
                    ng-repeat="item in utilities"
                    ng-class="{ 'btn-info': current == item.subject }"
                    ng-click="load( item.subject )"
                    snap-close>{{ item.title }}</button>
          </div>
        </accordion-group>
        <accordion-group>
          <accordion-heading>
            <button class="btn btn-primary btn-accordion full-width">Report</button>
          </accordion-heading>
          <div class="btn-group-vertical full-width" role="group">
            <button class="btn btn-default"
                    ng-repeat="item in reports"
                    ng-class="{ 'btn-info': current == item.subject }"
                    ng-click="load( item.subject )"
                    snap-close>{{ item.title }}</button>
          </div>
        </accordion-group>
      </accordian>
    </div>
    <div class="snap-drawer snap-drawer-right">
      This is where the settings content will go
    </div>
  </div>

  <snap-content snap-opt-tap-to-close="true" snap-opt-min-drag-distance="10000">
    <button snap-toggle="left" class="btn btn-primary menu-button rounded-top">Menu</button>
    <button snap-toggle="right" class="btn btn-primary settings-button rounded-top">Settings</button>
    <div class="container outer-container" data-snap-ignore="true">
      <div ng-view class="container view-frame"></div>
    </div>
  </snap-content>

</body>
</html>
