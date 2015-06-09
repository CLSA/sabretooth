define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceListCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceViewCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
