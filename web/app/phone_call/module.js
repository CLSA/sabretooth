define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'phone_call', true ); } catch( err ) { console.warn( err ); return; }
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
  cenozo.providers.directive( 'cnPhoneCallList', [
    'CnPhoneCallModelFactory',
    function( CnPhoneCallModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnPhoneCallModelFactory.root;
        }
      };
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
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnPhoneCallListFactory.instance( this );

        // need to explicitely disable the add and delete options
        this.enableAdd( false );
        this.enableDelete( false );
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
