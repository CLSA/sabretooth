define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentViewCtrl', [
    '$scope', 'CnAssignmentModelFactory', 'CnSession',
    function( $scope, CnAssignmentModelFactory, CnSession ) {
      $scope.model = CnAssignmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AssignmentHomeCtrl', [
    '$scope', 'CnAssignmentHomeFactory', 'CnSession',
    function( $scope, CnAssignmentHomeFactory, CnSession ) {
      $scope.model = CnAssignmentHomeFactory.instance();
      $scope.model.onLoad(); // breadcrumbs are handled by the service
    }
  ] );

} );
