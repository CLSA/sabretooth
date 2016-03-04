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
      help: 'Will remain blank until the questionnaire is finished.'
    },
    open_appointment_count: {
      type: 'hidden'
    },
    open_callback_count: {
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

        // enable the add button if all interviews are complete and another qnaire is available
        var self = this;
        this.afterList( function() {
          if( 'participant' == self.parentModel.getSubjectFromState() ) {
            self.parentModel.enableAdd( false );

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
              self.parentModel.enableAdd(
                null != queueRank &&
                null != qnaireRank && (
                  null == lastInterview || (
                    null != lastInterview.end_datetime &&
                    lastInterview.rank != qnaireRank
                  )
                )
              );
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
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // override onPatch
        this.onPatch = function( data ) {
          return this.$$onPatch( data ).then( function() {
            // if the end datetime has changed then reload then update the appointment/callback list actions
            if( angular.isDefined( data.end_datetime ) ) {
              var completed = null !== self.record.end_datetime;
              if( angular.isDefined( self.appointmentModel ) ) {
                self.appointmentModel.enableAdd( !completed && appointmentModule.actions.indexOf( 'add' ) );
                self.appointmentModel.enableDelete( !completed && appointmentModule.actions.indexOf( 'delete' ) );
                self.appointmentModel.enableEdit( !completed && appointmentModule.actions.indexOf( 'edit' ) );
                self.appointmentModel.enableView( !completed && appointmentModule.actions.indexOf( 'view' ) );
              }
              if( angular.isDefined( self.callbackModel ) ) {
                self.callbackModel.enableAdd( !completed && callbackModule.actions.indexOf( 'add' ) );
                self.callbackModel.enableDelete( !completed && callbackModule.actions.indexOf( 'delete' ) );
                self.callbackModel.enableEdit( !completed && callbackModule.actions.indexOf( 'edit' ) );
                self.callbackModel.enableView( !completed && callbackModule.actions.indexOf( 'view' ) );
              }
            }
          } );
        };

        // override onView
        this.onView = function() {
          return this.$$onView().then( function() {
            // if the end datetime has changed then update the appointment/callback list actions
            var completed = null !== self.record.end_datetime;
            var existing = 0 < self.record.open_appointment_count || 0 < self.record.open_callback_count;
            if( angular.isDefined( self.appointmentModel ) ) {
              var appointmentModule = cenozoApp.module( 'appointment' );
              self.appointmentModel.enableAdd(
                !completed && !existing && 0 <= appointmentModule.actions.indexOf( 'add' ) );
              self.appointmentModel.enableDelete(
                !completed && 0 <= appointmentModule.actions.indexOf( 'delete' ) );
              self.appointmentModel.enableEdit(
                !completed && 0 <= appointmentModule.actions.indexOf( 'edit' ) );
              self.appointmentModel.enableView(
                !completed && 0 <= appointmentModule.actions.indexOf( 'view' ) );
            }
            if( angular.isDefined( self.callbackModel ) ) {
              var callbackModule = cenozoApp.module( 'callback' );
              self.callbackModel.enableAdd(
                !completed && !existing && 0 <= callbackModule.actions.indexOf( 'add' ) );
              self.callbackModel.enableDelete(
                !completed && 0 <= callbackModule.actions.indexOf( 'delete' ) );
              self.callbackModel.enableEdit(
                !completed && 0 <= callbackModule.actions.indexOf( 'edit' ) );
              self.callbackModel.enableView(
                !completed && 0 <= callbackModule.actions.indexOf( 'view' ) );
            }
          } );
        };
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
          return $q.all( [

            this.$$getMetadata(),

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
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
