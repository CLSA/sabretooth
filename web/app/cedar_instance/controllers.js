define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceAddCtrl', [
    '$scope', 'CnCedarInstanceSingleton',
    function( $scope, CnCedarInstanceSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnCedarInstanceSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceListCtrl', [
    '$scope', '$location', 'CnCedarInstanceSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnCedarInstanceSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnCedarInstanceSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'CedarInstanceViewCtrl', [
    '$scope', '$routeParams', 'CnCedarInstanceSingleton',
    function( $scope, $routeParams, CnCedarInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnCedarInstanceSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
