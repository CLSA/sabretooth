define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireSingleton',
    function( $scope, CnQnaireSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnQnaireSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireListCtrl', [
    '$scope', '$location', 'CnQnaireSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnQnaireSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnQnaireSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QnaireViewCtrl', [
    '$scope', '$routeParams', 'CnQnaireSingleton',
    function( $scope, $routeParams, CnQnaireSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnQnaireSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
