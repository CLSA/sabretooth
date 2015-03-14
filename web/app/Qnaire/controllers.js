define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QuestionnaireAddCtrl', [
    '$scope', 'CnQuestionnaireSingleton',
    function( $scope, CnQuestionnaireSingleton ) {
      // use base class to create controller
      CnBaseAddCtrl.call( this, $scope, CnQuestionnaireSingleton );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QuestionnaireListCtrl', [
    '$scope', '$location', 'CnQuestionnaireSingleton', 'CnModalRestrictFactory',
    function( $scope, $location, CnQuestionnaireSingleton, CnModalRestrictFactory ) {
      CnBaseListCtrl.call( this, $scope, $location, CnQuestionnaireSingleton, CnModalRestrictFactory );
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.controller( 'QuestionnaireViewCtrl', [
    '$scope', '$routeParams', 'CnQuestionnaireSingleton',
    function( $scope, $routeParams, CnQuestionnaireSingleton ) {
      CnBaseViewCtrl.call( this, $scope, CnQuestionnaireSingleton );
      $scope.local.cnView.load( $routeParams.id );
    }
  ] );

} );
