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

} );
