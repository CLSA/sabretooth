define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhoneCallListCtrl', [
    '$scope', 'CnPhoneCallModelFactory',
    function( $scope, CnPhoneCallModelFactory ) {
      $scope.model = CnPhoneCallModelFactory.root;
      $scope.model.listModel.onList().then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( function exception( response ) {
        $scope.model.transitionToErrorState( response );
      } );
    }
  ] );

} );
