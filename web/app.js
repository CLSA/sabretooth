'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', '$state', 'CnBaseHeader', 'CnSession', 'CnHttpFactory', 'CnModalMessageFactory',
  function( $scope, $state, CnBaseHeader, CnSession, CnHttpFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    CnBaseHeader.construct( $scope );

    // add custom operations here by adding a new property to $scope.operationList

    // don't allow users to log out if they have an active assignment
    var logoutFunction = $scope.operationList.logout.execute;
    $scope.operationList.logout.execute = function() {
      // private function to redirect the user to the assignment view
      function showAssignmentExists() {
        var hasAccess = -1 < cenozoApp.module( 'assignment' ).actions.indexOf( 'home' );

        CnModalMessageFactory.instance( {
          title: 'Active Assignment Detected',
          message: hasAccess
                 ? 'You appear to have an open assignment.  You will now be redirected to this assignment ' +
                   'so that you can close it.  Once you have you will be able to log out.'
                 : 'You appear to have an open assignment which cannot be access from your current site and ' +
                   'role.  The site and role selection dialog will now be opened so that you can switch to ' +
                   'the site and role under which you started the assignment.  You will then need to view ' +
                   'the assignment and close it in order to log out.',
          error: true
        } ).show().then( function() {
          // check if the role has access to the assignment module
          if( hasAccess ) $state.go( 'assignment.home' );
          else CnSession.showSiteRoleModal();
        } );
      }

      CnHttpFactory.instance( {
        path: 'assignment/0',
        onError: function( response ) {
          if( 307 == response.status ) {
            // 307 means the user has no active assignment
            logoutFunction();
          } else if( 403 == response.status ) {
            // 403 means there is an assignment, but under a different site
            showAssignmentExists();
          } else { CnModalMessageFactory.httpError( response ); }
        }
      } ).get().then( function() {
        // active assignment detected
        showAssignmentExists();
      } );
    };
  }
] );
