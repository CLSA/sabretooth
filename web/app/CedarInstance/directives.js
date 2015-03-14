define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceAdd', function () {
    return {
      cedar_instanceUrl: 'app/cedar_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnCedarInstanceView', function () {
    return {
      cedar_instanceUrl: 'app/cedar_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
