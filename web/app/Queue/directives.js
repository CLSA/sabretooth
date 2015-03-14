define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueAdd', function () {
    return {
      queueUrl: 'app/queue/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQueueView', function () {
    return {
      queueUrl: 'app/queue/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
