define( cenozo.getDependencyList( 'interview' ), function() {
  'use strict';

  var module = cenozoApp.module( 'interview' );
  angular.extend( module, {
    identifier: {
      parent: {
        subject: 'participant',
        column: 'participant.uid'
      }
    },
    name: {
      singular: 'interview',
      plural: 'interviews',
      possessive: 'interview\'s',
      pluralPossessive: 'interviews\''
    },
    columnList: {
      uid: {
        column: 'participant.uid',
        title: 'UID'
      },
      qnaire: {
        column: 'script.name',
        title: 'Questionnaire'
      },
      site: {
        column: 'site.name',
        title: 'Credited Site'
      },
      start_datetime: {
        title: 'Start',
        type: 'datetimesecond'
      },
      end_datetime: {
        title: 'End',
        type: 'datetimesecond'
      }
    },
    defaultOrder: {
      column: 'start_datetime',
      reverse: true
    }
  } );

  module.addInputGroup( null, {
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum',
      constant: true
    },
    site_id: {
      title: 'Credited Site',
      type: 'enum',
      help: 'This determines which site is credited with the completed interview.'
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      max: 'end_datetime',
      help: 'When the first call from the first assignment was made for this interview.'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      min: 'start_datetime',
      max: 'now',
      help: 'Will remain blank until the questionnaire is complete.'
    },
    open_appointment_count: {
      type: 'hidden'
    },
    open_callback_count: {
      type: 'hidden'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewListCtrl', [
    '$scope', 'CnInterviewModelFactory', 'CnSession',
    function( $scope, CnInterviewModelFactory, CnSession ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'InterviewViewCtrl', [
    '$scope', 'CnInterviewModelFactory', 'CnSession',
    function( $scope, CnInterviewModelFactory, CnSession ) {
      $scope.model = CnInterviewModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewView', function () {
    return {
      templateUrl: 'app/interview/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory',
    cenozo.getViewModelInjectionList( 'interview' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, args );

        // override onPatch
        this.onPatch = function( data ) {
          return this.patchRecord( data ).then( function() {
            // if the end datetime has changed then reload then update the appointment/callback list actions
            if( angular.isDefined( data.end_datetime ) ) {
              var completed = null !== self.record.end_datetime;
              self.appointmentModel.enableAdd( !completed );
              self.appointmentModel.enableDelete( !completed );
              self.appointmentModel.enableEdit( !completed );
              self.appointmentModel.enableView( !completed );
              self.callbackModel.enableAdd( !completed );
              self.callbackModel.enableDelete( !completed );
              self.callbackModel.enableEdit( !completed );
              self.callbackModel.enableView( !completed );
            }
          } );
        };

        // override onView
        this.onView = function() {
          return this.viewRecord().then( function() {
            // if the end datetime has changed then reload then update the appointment/callback list actions
            var completed = null !== self.record.end_datetime;
            var existing = 0 < self.record.open_appointment_count || 0 < self.record.open_callback_count;
            self.appointmentModel.enableAdd( !completed && !existing );
            self.appointmentModel.enableDelete( !completed );
            self.appointmentModel.enableEdit( !completed );
            self.appointmentModel.enableView( !completed );
            self.callbackModel.enableAdd( !completed && !existing );
            self.callbackModel.enableDelete( !completed );
            self.callbackModel.enableEdit( !completed );
            self.callbackModel.enableView( !completed );
          } );
        };
      }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnInterviewListFactory, CnInterviewViewFactory,
              CnHttpFactory, $q ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this );

        // extend getBreadcrumbTitle
        this.getBreadcrumbTitle = function() {
          var qnaire = self.metadata.columnList.qnaire_id.enumList.findByProperty(
            'value', this.viewModel.record.qnaire_id );
          return qnaire ? qnaire.name : 'unknown';
        };

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {
            return $q.all( [
            
              CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', { table: 'script', column: 'name' } ] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.qnaire_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.qnaire_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } ),

              CnHttpFactory.instance( {
                path: 'site',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: 'name' }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.site_id.enumList = [];
                response.data.forEach( function( item ) {
                  self.metadata.columnList.site_id.enumList.push( { value: item.id, name: item.name } );
                } );
              } )

            ] ).then( function() { self.metadata.loadingCount--; } );
          } );
        };
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
