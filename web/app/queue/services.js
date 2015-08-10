define( cenozo.getServicesIncludeList( 'queue' ), function( module ) { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueViewFactory',
    cenozo.getListModelInjectionList( 'queue' ).concat( [ 'CnHttpFactory', function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var CnHttpFactory = args[args.length-1];
      var object = function( parentModel ) {
        CnBaseViewFactory.construct( this, parentModel, args );

        // make sure users can't add/remove participants from queues
        this.participantModel.enableChoose( false );

        // add operations
        var self = this;
        if( true ) {
          this.onView = function() {
            return this.viewRecord().then( function() {
              self.operationList = [ {
                name: 'Repopulate',
                execute: function() {
                  CnHttpFactory.instance( {
                    path: 'queue/' + self.record.id + '?repopulate=true'
                  } ).patch();
                }
              } ];
            } );
          };
        }
      }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } ] )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueModelFactory', [
    'CnBaseModelFactory', 'CnQueueListFactory', 'CnQueueViewFactory',
    function( CnBaseModelFactory, CnQueueListFactory, CnQueueViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnQueueListFactory.instance( this );
        this.viewModel = CnQueueViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueTreeFactory', [
    'CnHttpFactory',
    function( CnHttpFactory ) {
      var object = function() {
        var self = this;
        this.queueList = []; // one-dimensional list for manipulation
        this.queueTree = []; // multi-dimensional tree for display

        this.onView = function() {
          // blank out the participant counts if the tree is already built
          if( 0 < self.queueTree.length )
              for( var i = 1; i < self.queueList.length; i++ )
                if( angular.isDefined( self.queueList[i] ) ) self.queueList[i].participant_count = '...';

          return CnHttpFactory.instance( {
            path: 'queue?full=1',
            data: {
              modifier: { order: 'id' },
              select: { column:[ "id", "parent_queue_id", "rank", "name", "title", "participant_count" ] }
            }
          } ).query().then( function( response ) {
            if( 0 < self.queueTree.length ) {
              // don't rebuild the queue, just update the participant totals
              for( var i = 0; i < response.data.length; i++ )
                self.queueList[response.data[i].id].participant_count = response.data[i].participant_count;
            } else {
              // create an array containing all branches and add their child branches as we go
              for( var i = 0; i < response.data.length; i++ ) {
                // add all branches to the root, for now
                response.data[i].branchList = []; // will be filled in if the branch has any children
                response.data[i].initialOpen = "old participant" != response.data[i].name;
                response.data[i].open = response.data[i].initialOpen;
                self.queueList[response.data[i].id] = response.data[i];
                if( null !== response.data[i].parent_queue_id ) {
                  self.queueList[response.data[i].parent_queue_id].branchList.push( response.data[i] );
                }
              }

              // now put all root branches into the queue tree
              for( var i = 1; i < self.queueList.length; i++ )
                if( angular.isDefined( self.queueList[i] ) && null === self.queueList[i].parent_queue_id )
                  self.queueTree.push( self.queueList[i] );
            }
          } );
        };
      };

      return { instance: function() { return new object(); } };
    }
  ] );

} );
