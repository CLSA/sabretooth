define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueAddCtrl', [
    '$scope', '$state', 'CnQueueSingleton',
    function( $scope, $state, CnQueueSingleton ) {
      CnBaseAddCtrl.call( this, $scope, CnQueueSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueListCtrl', [
    '$scope', '$state', 'CnQueueSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnQueueSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnQueueSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnQueueSingleton',
    function( $scope, $state, $stateParams, CnQueueSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnQueueSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
