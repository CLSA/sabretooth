define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueStateAddCtrl', [
    '$scope', 'CnQueueStateModelFactory', 'CnSession',
    function( $scope, CnQueueStateModelFactory, CnSession ) {
      $scope.model = CnQueueStateModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueStateListCtrl', [
    '$scope', 'CnQueueStateModelFactory', 'CnSession',
    function( $scope, CnQueueStateModelFactory, CnSession ) {
      $scope.model = CnQueueStateModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
