define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  cenozo.providers.controller( 'AssignmentViewCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
