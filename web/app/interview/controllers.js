define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewAddCtrl', [
    '$scope', 'CnInterviewSingleton',
    function( $scope, CnInterviewSingleton ) {
      $scope.cnAdd = CnInterviewSingleton.cnAdd;
      $scope.cnList = CnInterviewSingleton.cnList;
      $scope.record = $scope.cnAdd.createRecord();
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewListCtrl', [
    '$scope', 'CnInterviewSingleton',
    function( $scope, CnInterviewSingleton ) {
      $scope.cnList = CnInterviewSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewViewCtrl', [
    '$stateParams', '$scope', 'CnInterviewSingleton',
    function( $stateParams, $scope, CnInterviewSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnInterviewSingleton );
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
