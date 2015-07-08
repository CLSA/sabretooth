'use strict';

var cenozo = angular.module( 'cenozo' );

cenozo.controller( 'HeaderCtrl', [
  '$scope', 'CnBaseHeader', 'CnHttpFactory', 'CnModalMessageFactory',
  function( $scope, CnBaseHeader, CnHttpFactory, CnModalMessageFactory ) {
    // copy all properties from the base header
    CnBaseHeader.construct( $scope );

    // add custom operations
    $scope.operationList.break = {
      title: 'Break',
      help: 'Go on break',
      execute: function() {
        return CnHttpFactory.instance( {
          path: 'self/0',
          data: { user: { break: true } }
        } ).patch().then( function() {
          CnModalMessageFactory.instance( {
            title: 'On Break',
            message: 'You are currently on break, to continue working click the "Close" button. ' +
                     'Your activity will continue to be logged as soon as you perform an action ' +
                     'or reload your web browser.'
          } ).show();
        } ).catch( function() {
          CnModalMessageFactory.instance( {
            title: 'Error',
            message: 'Sorry, there was an error while trying to put you on break. ' +
                     'As a result your time will continue to be logged. ' +
                     'Please contact support for help with this error.',
            error: true 
          } ).show();
        } );
      }
    };
  }
] );
