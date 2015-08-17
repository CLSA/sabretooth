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
    '$state', 'CnSession', 'CnParticipantModelFactory', 'CnModalConfirmFactory', 'CnHttpFactory',
    function( $state, CnSession, CnParticipantModelFactory, CnModalConfirmFactory, CnHttpFactory ) {
      var object = function() {
        var self = this;

        this.currentAssignment = null;
        this.participantSelectModel = CnParticipantModelFactory.instance();
        
        // add additional columns to the model
        this.participantSelectModel.addColumn( 'rank', { title: 'Rank', column: 'queue.rank', type: 'rank' }, 0 );
        this.participantSelectModel.addColumn( 'queue', { title: 'Queue', column: 'queue.name' }, 1 );
        this.participantSelectModel.addColumn( 'qnaire', { title: 'Questionnaire', column: 'qnaire.name' }, 2 );

        // override the default order
        this.participantSelectModel.listModel.orderBy( 'rank', true );

        // override the onChoose function
        this.participantSelectModel.listModel.onSelect = function( record ) {
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
                CnHttpFactory.instance( {
                  path: 'assignment/' + response.data,
                  data: { select: { column: [ 'id', 'start_datetime' ] } }
                } ).get().then( function( response ) {
                  self.currentAssignment = response.data;
                } );
              } ).catch( CnSession.errorHandler );
            }
          } );
        };

        // override the getServiceCollectionPath function
        this.participantSelectModel.getServiceCollectionPath = function() { return 'participant'; }
      };

      return { instance: function() { return new object(); } };
    }
  ] );

} );
