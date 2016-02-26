'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', '$state', 'CnBaseHeader', 'CnHttpFactory', 'CnModalMessageFactory',
  function( $scope, $state, CnBaseHeader, CnHttpFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    CnBaseHeader.construct( $scope );

    // add custom operations here by adding a new property to $scope.operationList

    // don't allow users to log out if they have an active assignment
    var logoutFunction = $scope.operationList.logout.execute;
    $scope.operationList.logout.execute = function() {
      CnHttpFactory.instance( {
        path: 'assignment/0',
        onError: function( response ) {
          if( 307 == response.status ) {
            // 307 means the user has no active assignment
            logoutFunction();
          } else { CnModalMessageFactory.httpError( response ); }
        }
      } ).get().then( function() {
        // active assignment detected, show warning and redirect so user can close it
        CnModalMessageFactory.instance( {
          title: 'Active Assignment Detected',
          message: 'You appear to have an open assignment.  You will now be redirected to this assignment ' +
                   'so that you can close it.  Once you have you will be able to logout.',
          error: true
        } ).show().then( function() { $state.go( 'assignment.home' ); } );
      } );
    };
  }
] );
