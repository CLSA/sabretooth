define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceAdd', function () {
    return {
      templateUrl: 'app/cedar_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceView', function () {
    return {
      templateUrl: 'app/cedar_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
