define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceAddCtrl', [
    '$scope', '$state', 'CnOpalInstanceSingleton',
    function( $scope, $state, CnOpalInstanceSingleton ) {
      CnBaseAddCtrl.call( this, $scope, CnOpalInstanceSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceListCtrl', [
    '$scope', '$state', 'CnOpalInstanceSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnOpalInstanceSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnOpalInstanceSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'OpalInstanceViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnOpalInstanceSingleton',
    function( $scope, $state, $stateParams, CnOpalInstanceSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnOpalInstanceSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
