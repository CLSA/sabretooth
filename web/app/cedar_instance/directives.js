define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceAdd', function () {
    return {
      templateUrl: 'app/CedarInstance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceView', function () {
    return {
      templateUrl: 'app/CedarInstance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
