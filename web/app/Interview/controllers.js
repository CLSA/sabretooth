define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewAddCtrl', [
    '$scope', 'CnInterviewSingleton',
    function( $scope, CnInterviewSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnInterviewSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewListCtrl', [
    '$scope', '$location', 'CnInterviewSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnInterviewSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnInterviewSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewViewCtrl', [
    '$scope', '$routeParams', 'CnInterviewSingleton',
    function( $scope, $routeParams, CnInterviewSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnInterviewSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
