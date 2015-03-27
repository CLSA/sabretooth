define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueAdd', function () {
    return {
      templateUrl: 'app/queue/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueView', function () {
    return {
      templateUrl: 'app/queue/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
