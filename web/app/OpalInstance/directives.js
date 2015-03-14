define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceAdd', function () {
    return {
      opal_instanceUrl: 'app/opal_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnOpalInstanceView', function () {
    return {
      opal_instanceUrl: 'app/opal_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
