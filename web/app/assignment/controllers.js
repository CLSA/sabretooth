define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentListCtrl', [
    '$scope', '$state', 'CnAssignmentSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnAssignmentSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnAssignmentSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnAssignmentSingleton',
    function( $scope, $state, $stateParams, CnAssignmentSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnAssignmentSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
