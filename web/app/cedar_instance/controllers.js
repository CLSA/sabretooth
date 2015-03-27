define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceAddCtrl', [
    '$scope', '$state', 'CnCedarInstanceSingleton',
    function( $scope, $state, CnCedarInstanceSingleton ) {
      CnBaseAddCtrl.call( this, $scope, $state, CnCedarInstanceSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceListCtrl', [
    '$scope', '$state', 'CnCedarInstanceSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnCedarInstanceSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnCedarInstanceSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnCedarInstanceSingleton',
    function( $scope, $state, $stateParams, CnCedarInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnCedarInstanceSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
