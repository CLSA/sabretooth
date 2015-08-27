define( cenozo.getServicesIncludeList( 'assignment' ).concat( cenozo.getModuleUrl( 'participant' ) + 'bootstrap.js' ), function( module ) {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentViewFactory',
    cenozo.getListModelInjectionList( 'assignment' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel, args ); }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentModelFactory', [
    '$state', 'CnBaseModelFactory', 'CnAssignmentListFactory', 'CnAssignmentViewFactory',
    function( $state, CnBaseModelFactory, CnAssignmentListFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnAssignmentListFactory.instance( this );
        this.viewModel = CnAssignmentViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentHomeFactory', [
    '$state', 'CnSession', 'CnHttpFactory',
    'CnParticipantModelFactory', 'CnModalMessageFactory',
    'CnModalConfirmFactory', 'CnModalParticipantNoteFactory',
    function( $state, CnSession, CnHttpFactory,
              CnParticipantModelFactory, CnModalMessageFactory,
              CnModalConfirmFactory, CnModalParticipantNoteFactory ) {
      var object = function() {
        var self = this;

        this.assignment = null;
        this.participant = null;
        this.participantModel = CnParticipantModelFactory.instance();
        
        // add additional columns to the model
        this.participantModel.addColumn( 'rank', { title: 'Rank', column: 'queue.rank', type: 'rank' }, 0 );
        this.participantModel.addColumn( 'queue', { title: 'Queue', column: 'queue.name' }, 1 );
        this.participantModel.addColumn( 'qnaire', { title: 'Questionnaire', column: 'qnaire.name' }, 2 );

        // override the default order
        this.participantModel.listModel.orderBy( 'rank', true );

        // override the onChoose function
        this.participantModel.listModel.onSelect = function( record ) {
          // attempt to assign the participant to the user
          CnModalConfirmFactory.instance( {
            title: 'Begin Assignment',
            message: 'Are you sure you wish to start a new assignment with participant ' + record.uid + '?'
          } ).show().then( function( response ) {
            if( response ) {
              self.isAssignmentLoading = true; // show loading screen right away
              CnHttpFactory.instance( {
                path: 'assignment?open=true',
                data: { participant_id: record.id }
              } ).post().then( function( response ) {
                self.onLoad();
              } ).catch( function( response ) {
                if( 409 == response.status ) {
                  // 409 means there is a conflict (the assignment can't be made)
                  CnModalMessageFactory.instance( {
                    title: 'Unable to start assignment with ' + record.uid,
                    message: response.data,
                    error: true
                  } ).show().then( self.onLoad );
                } else { CnSession.errorHandler(); }
              } );
            }
          } );
        };

        // override model functions
        this.participantModel.getServiceCollectionPath = function() { return 'participant'; }

        this.onLoad = function( showLoading ) {
          if( angular.isUndefined( showLoading ) ) showLoading = true;
          self.isAssignmentLoading = showLoading;
          self.isPrevAssignmentLoading = showLoading;
          return CnHttpFactory.instance( {
            path: 'assignment/0',
            data: { select: { column: [ 'id', 'interview_id', 'start_datetime',
              { table: 'participant', column: 'id', alias: 'participant_id' },
              { table: 'qnaire', column: 'name', alias: 'qnaire' },
              { table: 'queue', column: 'title', alias: 'queue' }
            ] } }
          } ).get().then( function success( response ) {
            self.assignment = response.data;
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id,
              data: { select: { column: [ 'id', 'uid', 'first_name', 'other_name', 'last_name',
                { table: 'language', column: 'name', alias: 'language' }
              ] } }
            } ).get().then( function success( response ) {
              self.participant = response.data;
              self.participant.getIdentifier = function() {
                return self.participantModel.getIdentifierFromRecord( this );
              };
              CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: self.participant.uid } ] );
              self.isAssignmentLoading = false;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'assignment/0/phone_call',
              data: { select: { column: [ 'end_datetime', 'status',
                { table: 'phone', column: 'rank' },
                { table: 'phone', column: 'type' },
                { table: 'phone', column: 'number' }
              ] } }
            } ).query().then( function success( response ) {
              self.phoneCallList = response.data;
              var len = self.phoneCallList.length
              self.activePhoneCall = 0 < len && null === self.phoneCallList[len-1].end_datetime
                                   ? self.phoneCallList[len-1]
                                   : null;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id +
                    '/interview/' + self.assignment.interview_id + '/assignment',
              data: {
                select: {
                  column: [
                    'start_datetime',
                    'end_datetime',
                    'phone_call_count',
                    { table: 'last_phone_call', column: 'status' },
                    { table: 'user', column: 'first_name' },
                    { table: 'user', column: 'last_name' },
                    { table: 'user', column: 'name' }
                  ]
                },
                modifier: { order: { start_datetime: true }, offset: 1, limit: 1 }
              }
            } ).query().then( function success( response ) {
              self.prevAssignment = 1 == response.data.length ? response.data[0] : null;
              self.isPrevAssignmentLoading = false;
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id + '/phone',
              data: { select: { column: [ 'id', 'rank', 'type', 'number', 'international' ] } }
            } ).query().then( function success( response ) {
              self.phoneList = response.data;
            } );
          } ).then( function() {
            if( angular.isUndefined( self.phoneCallStatusList ) ) {
              CnHttpFactory.instance( {
                path: 'phone_call'
              } ).head().then( function success( response ) {
                self.phoneCallStatusList =
                  cenozo.parseEnumList( angular.fromJson( response.headers( 'Columns' ) ).status );
              } );
            }
          } ).catch( function( response ) {
            if( 307 == response.status ) {
              // 307 means the user has no active assignment, so load the participant select list
              self.assignment = null;
              self.participant = null;
              self.isAssignmentLoading = false;
              self.isPrevAssignmentLoading = false;
              return self.participantModel.listModel.onList().then( function() {
                CnSession.setBreadcrumbTrail( [ { title: 'Assignment' }, { title: 'Select' } ] );
              } );
            } else { CnSession.errorHandler(); }
          } );
        };

        this.openNotes = function() {
          if( null != self.participant )
            CnModalParticipantNoteFactory.instance( { participant: self.participant } ).show();
        };

        this.startCall = function( phone ) {
          if( CnSession.voip_enabled && !phone.international ) {
            // TODO VOIP: start call
          }

          CnHttpFactory.instance( {
            path: 'phone_call?open=true',
            data: { phone_id: phone.id }
          } ).post().then( function() { self.onLoad( false ); } ).catch( CnSession.errorHandler );
        };

        this.endCall = function( status ) {
          if( CnSession.voip_enabled && !phone.international ) {
            // TODO VOIP: end call
          }

          CnHttpFactory.instance( {
            path: 'phone_call/0?close=true',
            data: { status: status }
          } ).patch().then( function() { self.onLoad( false ); } ).catch( CnSession.errorHandler );
        };

        this.endAssignment = function() {
          if( null != self.assignment ) {
            CnHttpFactory.instance( {
              path: 'assignment/0/phone_call'
            } ).query().then( function( response ) {
              CnHttpFactory.instance( { path: 'assignment/0?close=true', data: {} } ).patch().then( self.onLoad );
            } ).catch( function( response ) {
              if( 307 == response.status ) {
                // 307 means the user has no active assignment, so just refresh the page data
                self.onLoad();
              } else { CnSession.errorHandler(); }
            } );
          }
        };
      };

      return { instance: function() { return new object(); } };
    }
  ] );

} );
