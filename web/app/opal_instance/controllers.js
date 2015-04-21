define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceSingleton',
    function( $scope, CnOpalInstanceSingleton ) {
      $scope.cnAdd = CnOpalInstanceSingleton.cnAdd;
      $scope.cnList = CnOpalInstanceSingleton.cnList;
      CnOpalInstanceSingleton.promise.then( function() {
        $scope.record = $scope.cnAdd.createRecord();
      } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceListCtrl', [
    '$scope', 'CnOpalInstanceSingleton',
    function( $scope, CnOpalInstanceSingleton ) {
      $scope.cnList = CnOpalInstanceSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceViewCtrl', [
    '$stateParams', '$scope', 'CnOpalInstanceSingleton',
    function( $stateParams, $scope, CnOpalInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnOpalInstanceSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
