define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'SettingListCtrl', [
    '$scope', 'CnSettingModelFactory', 'CnSession',
    function( $scope, CnSettingModelFactory, CnSession ) {
      $scope.model = CnSettingModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  cenozo.providers.controller( 'SettingViewCtrl', [
    '$scope', 'CnSettingModelFactory', 'CnSession',
    function( $scope, CnSettingModelFactory, CnSession ) {
      $scope.model = CnSettingModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
