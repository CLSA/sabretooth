'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', '$state', 'CnBaseHeader', 'CnSession', 'CnHttpFactory',
  'CnModalConfirmFactory', 'CnModalMessageFactory',
  async function( $scope, $state, CnBaseHeader, CnSession, CnHttpFactory,
            CnModalConfirmFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    await CnBaseHeader.construct( $scope );

    // add custom operations here by adding a new property to $scope.operationList
    await CnSession.promise;

    CnSession.alertHeader = null == CnSession.user.assignment
                          ? undefined
                          : 'You are currently in an assignment';
    CnSession.onAlertHeader = async function() {
      if( angular.isDefined( cenozoApp.module( 'assignment' ).actions.control ) ) {
        await $state.go( 'assignment.control' );
      } else {
        await CnModalMessageFactory.instance( {
          title: 'Switch Roles For Assignment',
          message:
            'You cannot access your assignment under your current site and role. ' +
            'The site and role selection dialog will now be opened, please use it to switch to the site and ' +
            'role under which you started the assignment.\n\n' +
            'Once you have switched you will be able to access your assignment.',
          error: true
        } ).show();
        await CnSession.showSiteRoleModal();
      }
    };

    // don't allow users to log out if they have an active assignment
    var logoutOperation = $scope.operationList.findByProperty( 'title', 'Logout' );
    var logoutFunction = logoutOperation.execute;
    logoutOperation.execute = async function() {
      // private function to redirect the user to assignment-control
      async function showAssignmentExists() {
        var hasAccess = angular.isDefined( cenozoApp.module( 'assignment' ).actions.control );

        await CnModalMessageFactory.instance( {
          title: 'Active Assignment Detected',
          message: 'You cannot log out while in an open assignment!\n\n' + ( hasAccess
            ? 'In order to log out you will need to close your open assignment. ' +
              'You will now be redirected to your assignment.'
            : 'In order to log out you will need to close your open assignment, however, you cannot access ' +
              'your assignment from your current site and role. ' +
              'The site and role selection dialog will now be opened, please use it to switch to the site and ' +
              'role under which you started the assignment.\n\n' +
              'Once you have switched you will be able to access your assignment.'
          ),
          error: true
        } ).show();

        // check if the role has access to the assignment module
        if( hasAccess ) await $state.go( 'assignment.control' );
        else await CnSession.showSiteRoleModal();
      }

      await CnHttpFactory.instance( {
        path: 'assignment/0',
        onError: function( error ) {
          if( 307 == error.status ) {
            // 307 means the user has no active assignment
            logoutFunction();
          } else if( 403 == error.status ) {
            // 403 means there is an assignment, but under a different site
            showAssignmentExists();
          } else { CnModalMessageFactory.httpError( error ); }
        }
      } ).get();

      // active assignment detected
      await showAssignmentExists();
    };
  }
] );
