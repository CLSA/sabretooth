define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentAddCtrl', [
    '$scope', 'CnAppointmentModelFactory', 'CnSession',
    function( $scope, CnAppointmentModelFactory, CnSession ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentListCtrl', [
    '$scope', 'CnAppointmentModelFactory', 'CnSession',
    function( $scope, CnAppointmentModelFactory, CnSession ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentViewCtrl', [
    '$scope', 'CnAppointmentModelFactory', 'CnSession',
    function( $scope, CnAppointmentModelFactory, CnSession ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
