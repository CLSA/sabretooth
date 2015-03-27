define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceAdd', function () {
    return {
      templateUrl: 'app/opal_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceView', function () {
    return {
      templateUrl: 'app/opal_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
