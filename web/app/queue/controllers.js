define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueListCtrl', [
    '$scope', 'CnQueueModelFactory',
    function( $scope, CnQueueModelFactory ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.listModel.onList().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueViewCtrl', [
    '$scope', 'CnQueueModelFactory',
    function( $scope, CnQueueModelFactory ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.viewModel.onView().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
