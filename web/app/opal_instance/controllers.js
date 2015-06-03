define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceListCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.listModel.onList().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceViewCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.viewModel.onView().catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
