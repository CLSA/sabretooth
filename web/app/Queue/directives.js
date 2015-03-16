define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueAdd', function () {
    return {
      templateUrl: 'app/Queue/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueView', function () {
    return {
      templateUrl: 'app/Queue/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
