define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhoneCallListCtrl', [
    '$scope', 'CnPhoneCallModelFactory', 'CnSession',
    function( $scope, CnPhoneCallModelFactory, CnSession ) {
      $scope.model = CnPhoneCallModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

} );
