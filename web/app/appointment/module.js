define( function() {
  'use strict';

  try { cenozoApp.module( 'appointment', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'appointment' ), {
    identifier: {
      parent: {
        subject: 'interview',
        column: 'interview_id',
        friendly: 'qnaire'
      }
    },
    name: {
      singular: 'appointment',
      plural: 'appointments',
      possessive: 'appointment\'s',
      pluralPossessive: 'appointments\''
    },
    columnList: {
      datetime: {
        type: 'datetime',
        title: 'Date & Time'
      },
      phone: {
        column: 'phone.name',
        type: 'string',
        title: 'Number'
      },
      user: {
        column: 'user.name',
        type: 'string',
        title: 'Reserved For'
      },
      assignment_user: {
        column: 'assignment_user.name',
        type: 'string',
        title: 'Assigned to'
      },
      type: {
        type: 'string',
        title: 'Type'
      },
      state: {
        type: 'string',
        title: 'State',
        help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
      }
    },
    defaultOrder: {
      column: 'datetime',
      reverse: true
    }
  } );

  cenozoApp.module( 'appointment' ).addInputGroup( null, {
    datetime: {
      title: 'Date & Time',
      type: 'datetime',
      min: 'now',
      help: 'Cannot be changed once the appointment has passed.'
    },
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire: {
      column: 'script.name',
      title: 'Questionnaire',
      type: 'string',
      constant: true
    },
    phone_id: {
      title: 'Phone Number',
      type: 'enum',
      help: 'Which number should be called for the appointment, or leave this field blank if any of the ' +
            'participant\'s phone numbers can be called.'
    },
    user_id: {
      column: 'appointment.user_id',
      title: 'Reserved for',
      type: 'lookup-typeahead',
      typeahead: {
        table: 'user',
        select: 'CONCAT( first_name, " ", last_name, " (", name, ")" )',
        where: [ 'first_name', 'last_name', 'name' ]
      },
      help: 'The user the appointment is specifically reserved for. ' +
            'Cannot be changed once the appointment has passed.'
    },
    assignment_user: {
      column: 'assignment_user.name',
      title: 'Assigned to',
      type: 'string',
      constant: true,
      help: 'This will remain blank until the appointment has been assigned. The assigned user can only be ' +
            ' different from the reserved user when the appointment was missed.'
    },
    state: {
      title: 'State',
      type: 'string',
      constant: true,
      help: 'One of reached, not reached, upcoming, assignable, missed, incomplete, assigned or in progress'
    },
    type: {
      title: 'Type',
      type: 'enum'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentAddCtrl', [
    '$scope', 'CnAppointmentModelFactory',
    function( $scope, CnAppointmentModelFactory ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentListCtrl', [
    '$scope', 'CnAppointmentModelFactory',
    function( $scope, CnAppointmentModelFactory ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'AppointmentViewCtrl', [
    '$scope', 'CnAppointmentModelFactory',
    function( $scope, CnAppointmentModelFactory ) {
      $scope.model = CnAppointmentModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', function() {
    return {
      templateUrl: 'app/appointment/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentView', function() {
    return {
      templateUrl: 'app/appointment/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) {
      var self = this;
        CnBaseListFactory.construct( this, parentModel );

        // override onDelete
        this.onDelete = function( record ) {
          return this.$$onDelete( record ).then( function() { self.parentModel.enableAdd( 0 == self.total ); } );
        };
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.onView = function() {
          return this.$$onView().then( function() {
            var upcoming = moment().isBefore( self.record.datetime );
            parentModel.enableDelete( upcoming );
            parentModel.enableEdit( upcoming );
          } );
        };
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAppointmentModelFactory', [
    'CnBaseModelFactory', 'CnAppointmentAddFactory', 'CnAppointmentListFactory', 'CnAppointmentViewFactory',
    'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnAppointmentAddFactory, CnAppointmentListFactory, CnAppointmentViewFactory,
              CnHttpFactory, $q ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'appointment' ) );
        this.addModel = CnAppointmentAddFactory.instance( this );
        this.listModel = CnAppointmentListFactory.instance( this );
        this.viewModel = CnAppointmentViewFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          var promiseList = [ this.loadMetadata() ];

          var parent = this.getParentIdentifier();
          if( angular.isDefined( parent.subject ) && angular.isDefined( parent.identifier ) ) {
            promiseList.push(
              CnHttpFactory.instance( {
                path: [ parent.subject, parent.identifier ].join( '/' ),
                data: { select: { column: { column: 'participant_id' } } }
              } ).query().then( function( response ) {
                return CnHttpFactory.instance( {
                  path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                  data: {
                    select: { column: [ 'id', 'rank', 'type', 'number' ] },
                    modifier: { order: { rank: false } }
                  }
                } ).query().then( function( response ) {
                  self.metadata.columnList.phone_id.enumList = [];
                  response.data.forEach( function( item ) {
                    self.metadata.columnList.phone_id.enumList.push( {
                      value: item.id,
                      name: '(' + item.rank + ') ' + item.type + ': ' + item.number
                    } );
                  } );
                } )
              } )
            );
          }

          return $q.all( promiseList ).finally( function finished() { self.metadata.loadingCount--; } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
