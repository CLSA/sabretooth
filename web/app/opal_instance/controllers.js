define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceModelFactory', 'CnSession',
    function( $scope, CnOpalInstanceModelFactory, CnSession ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceListCtrl', [
    '$scope', 'CnOpalInstanceModelFactory', 'CnSession',
    function( $scope, CnOpalInstanceModelFactory, CnSession ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceViewCtrl', [
    '$scope', 'CnOpalInstanceModelFactory', 'CnSession',
    function( $scope, CnOpalInstanceModelFactory, CnSession ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
