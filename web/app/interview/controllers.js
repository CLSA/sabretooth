define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewListCtrl', [
    '$scope', 'CnInterviewModelFactory', 'CnSession',
    function( $scope, CnInterviewModelFactory, CnSession ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewViewCtrl', [
    '$scope', 'CnInterviewModelFactory', 'CnSession',
    function( $scope, CnInterviewModelFactory, CnSession ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
