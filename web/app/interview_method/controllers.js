define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewMethodListCtrl', [
    '$scope', 'CnInterviewMethodModelFactory', 'CnSession',
    function( $scope, CnInterviewMethodModelFactory, CnSession ) {
      $scope.model = CnInterviewMethodModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
