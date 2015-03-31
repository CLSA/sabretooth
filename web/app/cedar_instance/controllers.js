define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceAddCtrl', [
    '$scope', 'CnCedarInstanceSingleton',
    function( $scope, CnCedarInstanceSingleton ) {
      $scope.cnAdd = CnCedarInstanceSingleton.cnAdd;
      $scope.cnList = CnCedarInstanceSingleton.cnList;
      $scope.record = $scope.cnAdd.createRecord();
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceListCtrl', [
    '$scope', 'CnCedarInstanceSingleton',
    function( $scope, CnCedarInstanceSingleton ) {
      $scope.cnList = CnCedarInstanceSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceViewCtrl', [
    '$stateParams', '$scope', 'CnCedarInstanceSingleton',
    function( $stateParams, $scope, CnCedarInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnCedarInstanceSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
