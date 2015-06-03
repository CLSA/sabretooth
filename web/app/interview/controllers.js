define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewAddCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewListCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.listModel.onList().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewViewCtrl', [
    '$scope', 'CnInterviewModelFactory',
    function( $scope, CnInterviewModelFactory ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.viewModel.onView().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
