define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentListCtrl', [
    '$scope', 'CnAssignmentSingleton',
    function( $scope, CnAssignmentSingleton ) {
      $scope.cnList = CnAssignmentSingleton.cnList;
      $scope.cnList.load().catch( function exception() { cnFatalError(); } );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentViewCtrl', [
    '$stateParams', '$scope', 'CnAssignmentSingleton',
    function( $stateParams, $scope, CnAssignmentSingleton ) {
      $scope.cnList = CnAssignmentSingleton.cnList;
      $scope.cnView = CnAssignmentSingleton.cnView;
      $scope.cnView.load( $stateParams.id ).catch( function exception() { cnFatalError(); } );
      $scope.patch = cnPatch( $scope );
    }
  ] );

} );
