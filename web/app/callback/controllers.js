define( [], function() { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CallbackAddCtrl', [
    '$scope', 'CnCallbackModelFactory', 'CnSession',
    function( $scope, CnCallbackModelFactory, CnSession ) {
      $scope.model = CnCallbackModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CallbackListCtrl', [
    '$scope', 'CnCallbackModelFactory', 'CnSession',
    function( $scope, CnCallbackModelFactory, CnSession ) {
      $scope.model = CnCallbackModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CallbackViewCtrl', [
    '$scope', 'CnCallbackModelFactory', 'CnSession',
    function( $scope, CnCallbackModelFactory, CnSession ) {
      $scope.model = CnCallbackModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
