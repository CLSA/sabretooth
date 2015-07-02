define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhaseAddCtrl', [
    '$scope', 'CnPhaseModelFactory', 'CnSession',
    function( $scope, CnPhaseModelFactory, CnSession ) {
      $scope.model = CnPhaseModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhaseListCtrl', [
    '$scope', 'CnPhaseModelFactory', 'CnSession',
    function( $scope, CnPhaseModelFactory, CnSession ) {
      $scope.model = CnPhaseModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhaseViewCtrl', [
    '$scope', 'CnPhaseModelFactory', 'CnSession',
    function( $scope, CnPhaseModelFactory, CnSession ) {
      $scope.model = CnPhaseModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
