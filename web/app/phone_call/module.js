define( function() {
  'use strict';

  try { var url = cenozoApp.module( 'phone_call', true ).url; } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'phone_call' ), {
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
        templateUrl: url + 'list.tpl.html',
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnPhoneCallModelFactory.root;
          $scope.model.listModel.onList( true ).then( function() {
            $scope.model.setupBreadcrumbTrail( 'list' );
          } );
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
        CnBaseModelFactory.construct( this, cenozoApp.module( 'phone_call' ) );
        this.listModel = CnPhoneCallListFactory.instance( this );
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
