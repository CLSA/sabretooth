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
              CnHttpFactory.instance( {
                path: 'assignment',
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

        this.onLoad = function() {
          return CnHttpFactory.instance( {
            path: 'assignment/0',
            data: { select: { column: [ 'id', 'start_datetime',
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
            } );
          } ).then( function() {
            CnHttpFactory.instance( {
              path: 'participant/' + self.assignment.participant_id + '/phone',
              data: { select: { column: [ 'id', 'rank', 'type', 'number' ] } }
            } ).query().then( function success( response ) {
              self.phoneList = response.data;
            } );
          } ).catch( function( response ) {
            if( 307 == response.status ) {
              // 307 means the user has no active assignment, so load the participant select list
              self.assignment = null;
              self.participant = null;
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

        this.endAssignment = function() {
          if( null != self.assignment ) {
            CnHttpFactory.instance( {
              path: 'assignment/0/phone_call'
            } ).query().then( function( response ) {
              // delete the assignment if there are no phone calls, or close it if there are
              var promise = 0 == response.data.length
                          ? CnHttpFactory.instance( { path: 'assignment/0' } ).delete()
                          : CnHttpFactory.instance( { path: 'assignment/0', data: { close: true } } ).patch();
              return promise.then( function() { self.onLoad(); } );
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
