define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewAddCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewListCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewViewCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
