define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireSingleton',
    function( $scope, CnQnaireSingleton ) {
      $scope.cnAdd = CnQnaireSingleton.cnAdd;
      $scope.cnList = CnQnaireSingleton.cnList;
      CnQnaireSingleton.promise.then( function() {
        $scope.record = $scope.cnAdd.createRecord();
      } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireListCtrl', [
    '$scope', 'CnQnaireSingleton',
    function( $scope, CnQnaireSingleton ) {
      $scope.cnList = CnQnaireSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireViewCtrl', [
    '$stateParams', '$scope', 'CnQnaireSingleton',
    function( $stateParams, $scope, CnQnaireSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnQnaireSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
