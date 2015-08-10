define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueListCtrl', [
    '$scope', 'CnQueueModelFactory', 'CnSession',
    function( $scope, CnQueueModelFactory, CnSession ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueViewCtrl', [
    '$scope', 'CnQueueModelFactory', 'CnSession',
    function( $scope, CnQueueModelFactory, CnSession ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueTreeCtrl', [
    '$scope', 'CnQueueTreeFactory', 'CnSession',
    function( $scope, CnQueueTreeFactory, CnSession ) {
      $scope.isLoading = false;
      $scope.isComplete = false;
      $scope.model = CnQueueTreeFactory.instance();
      $scope.refresh = function() {
        $scope.isLoading = 0 < this.model.queueTree.length;
        $scope.isComplete = 0 < this.model.queueTree.length;
        $scope.model.onView().then(
          function() { $scope.isLoading = false; $scope.isComplete = true; },
          CnSession.errorHandler
        );
      };

      $scope.refresh();
    }
  ] );

} );
