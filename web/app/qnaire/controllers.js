define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireListCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireViewCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
