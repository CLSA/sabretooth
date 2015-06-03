define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireListCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.listModel.onList().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireViewCtrl', [
    '$stateParams', '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.viewModel.onView().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
