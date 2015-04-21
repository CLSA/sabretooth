define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueAddCtrl', [
    '$scope', 'CnQueueSingleton',
    function( $scope, CnQueueSingleton ) {
      $scope.cnAdd = CnQueueSingleton.cnAdd;
      $scope.cnList = CnQueueSingleton.cnList;
      CnQueueSingleton.promise.then( function() {
        $scope.record = $scope.cnAdd.createRecord();
      } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueListCtrl', [
    '$scope', 'CnQueueSingleton',
    function( $scope, CnQueueSingleton ) {
      $scope.cnList = CnQueueSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QueueViewCtrl', [
    '$stateParams', '$scope', 'CnQueueSingleton',
    function( $stateParams, $scope, CnQueueSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnQueueSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
