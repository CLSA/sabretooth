define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCallbackAdd', function () {
    return {
      templateUrl: 'app/callback/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCallbackView', function () {
    return {
      templateUrl: 'app/callback/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
