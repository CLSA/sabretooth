define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentModelFactory',
    function( $scope, CnAssignmentModelFactory ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.listModel.onList().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  cenozo.providers.controller( 'AssignmentViewCtrl', [
    '$scope', 'CnAssignmentModelFactory',
    function( $scope, CnAssignmentModelFactory ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.viewModel.onView().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
