define( cenozo.getDependencyList( 'phone_call' ), function() {
  'use strict';

  var module = cenozoApp.module( 'phone_call' );
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'assignment',
        column: 'assignment_id'
      }
    },
    name: {
      singular: 'phone call',
      plural: 'phone calls',
      possessive: 'phone call\'s',
      pluralPossessive: 'phone calls\''
    },
    inputList: {
      // not used
    },
    columnList: {
      phone: {
        column: 'phone.type',
        title: 'Phone'
      },
      start_datetime: {
        column: 'phone_call.start_datetime',
        title: 'Start',
        type: 'datetimesecond',
        max: 'now'
      },
      end_datetime: {
        column: 'phone_call.end_datetime',
        title: 'End',
        type: 'datetimesecond',
        max: 'now'
      },
      status: { title: 'Status' }
    },
    defaultOrder: {
      column: 'start_datetime',
      reverse: true
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'PhoneCallListCtrl', [
    '$scope', 'CnPhoneCallModelFactory', 'CnSession',
    function( $scope, CnPhoneCallModelFactory, CnSession ) {
      $scope.model = CnPhoneCallModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnPhoneCallListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnPhoneCallModelFactory', [
    '$state', 'CnBaseModelFactory', 'CnPhoneCallListFactory',
    function( $state, CnBaseModelFactory, CnPhoneCallListFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnPhoneCallListFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
