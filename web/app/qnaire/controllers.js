define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireAddCtrl', [
    '$scope', '$state', 'CnQnaireSingleton',
    function( $scope, $state, CnQnaireSingleton ) {
      CnBaseAddCtrl.call( this, $scope, CnQnaireSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireListCtrl', [
    '$scope', '$state', 'CnQnaireSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnQnaireSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnQnaireSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnQnaireSingleton',
    function( $scope, $state, $stateParams, CnQnaireSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnQnaireSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
