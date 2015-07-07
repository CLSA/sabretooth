define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceAddCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceListCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceViewCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
