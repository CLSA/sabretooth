define( [], function() {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireModelFactory', 'CnSession',
    function( $scope, CnQnaireModelFactory, CnSession ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireListCtrl', [
    '$scope', 'CnQnaireModelFactory', 'CnSession',
    function( $scope, CnQnaireModelFactory, CnSession ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireViewCtrl', [
    '$scope', 'CnQnaireModelFactory', 'CnSession',
    function( $scope, CnQnaireModelFactory, CnSession ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
