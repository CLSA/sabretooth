define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'interview', true ); } catch( err ) { console.warn( err ); return; }
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

  module.addInputGroup( '', {
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
      help: 'Will remain blank until the questionnaire is finished.'
    },
    future_appointment: {
      type: 'hidden'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewList', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewView', [
    'CnInterviewModelFactory',
    function( CnInterviewModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnInterviewModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory', 'CnHttpFactory',
    function( CnBaseListFactory, CnHttpFactory ) {
      var object = function( parentModel ) {
        CnBaseListFactory.construct( this, parentModel );

        // enable the add button if:
        //   1) the interview list's parent is a participant model
        //   2) all interviews are complete for this participant
        //   3) another qnaire is available for this participant
        var self = this;
        this.afterList( function() {
          self.parentModel.getAddEnabled = function() { return false; };
          if( 'participant' == self.parentModel.getSubjectFromState() ) {
            var queueRank = null;
            var qnaireRank = null;
            var lastInterview = null;
            // get the participant's last interview
            CnHttpFactory.instance( {
              path: self.parentModel.getServiceCollectionPath(),
              data: {
                modifier: { order: { 'qnaire.rank': true }, limit: 1 },
                select: { column: [ { table: 'qnaire', column: 'rank' }, 'end_datetime' ] }
              },
              onError: function( response ) {} // ignore errors
            } ).query().then( function( response ) {
              if( 0 < response.data.length ) lastInterview = response.data[0];

              // get the participant's current queue rank
              return CnHttpFactory.instance( {
                path: self.parentModel.getServiceCollectionPath().replace( '/interview', '' ),
                data: {
                  select: { column: [
                    { table: 'queue', column: 'rank', alias: 'queueRank' },
                    { table: 'qnaire', column: 'rank', alias: 'qnaireRank' }
                  ] }
                },
                onError: function( response ) {} // ignore errors
              } ).query().then( function( response ) {
                queueRank = response.data.queueRank;
                qnaireRank = response.data.qnaireRank;
              } );
            } ).then( function( response ) {
              self.parentModel.getAddEnabled = function() {
                return self.parentModel.$$getAddEnabled() &&
                       null != queueRank &&
                       null != qnaireRank && (
                         null == lastInterview || (
                           null != lastInterview.end_datetime &&
                           lastInterview.rank != qnaireRank
                         )
                       );
              };
            } );
          }
        } );
      };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        function getAppointmentEnabled( type ) {
          var completed = null !== self.record.end_datetime;
          var future = self.record.future_appointment;
          return 'add' == type ? ( !completed && !future ) : future;
        }

        function updateEnableFunctions() {
          self.appointmentModel.getAddEnabled = function() {
            return angular.isDefined( self.appointmentModel.module.actions.add ) &&
                   getAppointmentEnabled( 'add' );
          };
          self.appointmentModel.getDeleteEnabled = function() {
            return angular.isDefined( self.appointmentModel.module.actions.delete ) &&
                   getAppointmentEnabled( 'delete' );
          };
        }

        // override onView
        this.onView = function() {
          return this.$$onView().then( function() {
            if( angular.isDefined( self.appointmentModel ) ) updateEnableFunctions();
          } );
        };

        // override appointment list's onDelete
        this.deferred.promise.then( function() {
          if( angular.isDefined( self.appointmentModel ) ) {
            self.appointmentModel.listModel.onDelete = function( record ) {
              return self.appointmentModel.listModel.$$onDelete( record ).then( function() { self.onView(); } );
            };
          }
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnHttpFactory', 'CnModalMessageFactory', '$q',
    function( CnBaseModelFactory, CnInterviewListFactory, CnInterviewViewFactory,
              CnHttpFactory, CnModalMessageFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this, root );

        // Adding an interview is special, instead of transitioning to an add dialog a command can be
        // sent to the server to directly add a new interview so long as there doesn't exist an incomplete
        // interview and there is another qnaire available
        this.transitionToAddState = function() {
          return CnHttpFactory.instance( {
            path: self.getServiceCollectionPath(),
            data: {}, // no record required, the server will fill in all necessary values
            onError: function( response ) {
              if( 409 == response.status ) {
                // 409 when we can't add a new interview (explanation will be provided
                CnModalMessageFactory.instance( {
                  title: 'Unable To Add Interview',
                  message: response.data +
                           ' This is likely caused by the list being out of date so it will now be refreshed.',
                  error: true
                } ).show().then( function() {
                  self.listModel.onList( true );
                } );
              } else CnModalMessageFactory.httpError( response );
            }
          } ).post().then( function() {
            self.listModel.onList( true );
          } );
        };

        // extend getBreadcrumbTitle
        // (metadata's promise will have already returned so we don't have to wait for it)
        this.getBreadcrumbTitle = function() {
          var qnaire = self.metadata.columnList.qnaire_id.enumList.findByProperty(
            'value', this.viewModel.record.qnaire_id );
          return qnaire ? qnaire.name : 'unknown';
        };

        // extend getMetadata
        this.getMetadata = function() {
          return this.$$getMetadata().then( function() {
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
            ] );
          } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
