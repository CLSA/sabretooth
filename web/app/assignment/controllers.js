define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentModelFactory',
    function( $scope, CnAssignmentModelFactory ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  cenozo.providers.controller( 'AssignmentViewCtrl', [
    '$scope', 'CnAssignmentModelFactory',
    function( $scope, CnAssignmentModelFactory ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
