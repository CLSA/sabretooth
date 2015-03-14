define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceSingleton',
    function( $scope, CnOpalInstanceSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnOpalInstanceSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceListCtrl', [
    '$scope', '$location', 'CnOpalInstanceSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnOpalInstanceSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnOpalInstanceSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceViewCtrl', [
    '$scope', '$routeParams', 'CnOpalInstanceSingleton',
    function( $scope, $routeParams, CnOpalInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnOpalInstanceSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
