define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewAddCtrl', [
    '$scope', '$state', 'CnInterviewSingleton',
    function( $scope, $state, CnInterviewSingleton ) {
      CnBaseAddCtrl.call( this, $scope, CnInterviewSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewListCtrl', [
    '$scope', '$state', 'CnInterviewSingleton', 'CnModalRestrictFactory',
    function( $scope, $state, CnInterviewSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $state, CnInterviewSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'InterviewViewCtrl', [
    '$scope', '$state', '$stateParams', 'CnInterviewSingleton',
    function( $scope, $state, $stateParams, CnInterviewSingleton ) {
      CnBaseViewCtrl.call( this, $scope, $state, CnInterviewSingleton );
      $scope.local.cnView.load( $stateParams.id );
    }
  ] );

} );
