define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceAdd', function () {
    return {
      templateUrl: 'app/OpalInstance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceView', function () {
    return {
      templateUrl: 'app/OpalInstance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
