define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueAddCtrl', [
    '$scope', 'CnQueueSingleton',
    function( $scope, CnQueueSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnQueueSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueListCtrl', [
    '$scope', '$location', 'CnQueueSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnQueueSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnQueueSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueViewCtrl', [
    '$scope', '$routeParams', 'CnQueueSingleton',
    function( $scope, $routeParams, CnQueueSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnQueueSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
