define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentAddCtrl', [
    '$scope', 'CnAssignmentSingleton',
    function( $scope, CnAssignmentSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnAssignmentSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentListCtrl', [
    '$scope', '$location', 'CnAssignmentSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnAssignmentSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnAssignmentSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'AssignmentViewCtrl', [
    '$scope', '$routeParams', 'CnAssignmentSingleton',
    function( $scope, $routeParams, CnAssignmentSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnAssignmentSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
