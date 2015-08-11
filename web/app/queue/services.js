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
    'CnSession', 'CnHttpFactory', 'CnModalDatetimeFactory',
    function( CnSession, CnHttpFactory, CnModalDatetimeFactory ) {
      var object = function() {
        var self = this;
        this.queueList = []; // one-dimensional list for manipulation
        this.queueTree = []; // multi-dimensional tree for display

        this.form = {
          qnaire_id: undefined,
          qnaireList: null,
          site_id: undefined,
          siteList: null,
          viewingDate: null,
          formattedViewingDate: 'Now',
          language_id: undefined,
          languageList: null,
          selectViewingDate: function( onChange ) {
            return CnModalDatetimeFactory.instance( {
              title: 'Select Viewing Date & Time',
              date: self.form.viewingDate,
              minDate: 'today',
              pickerType: 'date',
              emptyAllowed: true
            } ).show().then( function( response ) {
              if( false !== response ) {
                self.form.viewingDate = response;
                self.form.formattedViewingDate = null === response
                                               ? 'Now'
                                               : CnSession.formatValue( response, 'date', false );
                if( angular.isDefined( onChange ) ) onChange();
              }
            } );
          }
        };

        this.onView = function() {
          // blank out the button title if the tree is already built
          if( 0 < self.queueTree.length ) {
            for( var i = 1; i < self.queueList.length; i++ ) {
              if( angular.isDefined( self.queueList[i] ) ) {
                self.queueList[i].participant_count = 0;
                self.queueList[i].childTotal = 0;
                self.queueList[i].button.name = '\u2026';
              }
            }
          }

          if( null == self.form.qnaireList ) {
            CnHttpFactory.instance( {
              path: 'qnaire',
              data: { select: { column: [ 'id', 'name' ] }, modifier: { order: 'rank' } }
            } ).query().then( function( response ) {
              self.form.qnaireList = [ { value: undefined, name: 'Any' } ];
              for( var i = 0; i < response.data.length; i++ ) {
                self.form.qnaireList.push( { value: response.data[i].id, name: response.data[i].name } );
              }
            } );
          }
          
          if( null == self.form.siteList && CnSession.role.allSites ) {
            CnHttpFactory.instance( {
              path: 'site',
              data: { select: { column: [ 'id', 'name' ] }, modifier: { order: 'name' } }
            } ).query().then( function( response ) {
              self.form.siteList = [ { value: undefined, name: 'All' } ];
              for( var i = 0; i < response.data.length; i++ ) {
                self.form.siteList.push( { value: response.data[i].id, name: response.data[i].name } );
              }
            } );
          }

          if( null == self.form.languageList ) {
            CnHttpFactory.instance( {
              path: 'language',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { where: { column: 'active', operator: '=', value: true }, order: 'name' }
              }
            } ).query().then( function( response ) {
              self.form.languageList = [ { value: undefined, name: 'Any' } ];
              for( var i = 0; i < response.data.length; i++ ) {
                self.form.languageList.push( { value: response.data[i].id, name: response.data[i].name } );
              }
            } );
          }

          var whereList = [];
          if( angular.isDefined( self.form.qnaire_id ) )
            whereList.push( { column: 'qnaire_id', operator: '=', value: self.form.qnaire_id } );
          if( angular.isDefined( self.form.site_id ) )
            whereList.push( { column: 'site_id', operator: '=', value: self.form.site_id } );
          if( null !== self.form.viewingDate )
            whereList.push( { column: 'date', operator: '=', value: self.form.viewingDate } );
          if( angular.isDefined( self.form.language_id ) )
            whereList.push( { column: 'language_id', operator: '=', value: self.form.language_id } );

          return CnHttpFactory.instance( {
            path: 'queue?full=1',
            data: {
              modifier: {
                order: 'id',
                where: whereList
              },
              select: { column: [ "id", "parent_queue_id", "rank", "name", "title", "participant_count" ] }
            }
          } ).query().then( function( response ) {
            if( 0 < self.queueTree.length ) {
              // don't rebuild the queue, just update the participant totals
              for( var i = 0; i < response.data.length; i++ ) {
                self.queueList[response.data[i].id].participant_count = response.data[i].participant_count;
                self.queueList[response.data[i].id].button.name = response.data[i].participant_count;
              }
            } else {
              // create an array containing all branches and add their child branches as we go
              var eligibleQueueId = null;
              for( var i = 0; i < response.data.length; i++ ) {
                // make note of the eligible queue
                if( null === eligibleQueueId && 'eligible' == response.data[i].name )
                  eligibleQueueId = response.data[i].id;

                // add all branches to the root, for now
                response.data[i].branchList = []; // will be filled in if the branch has any children
                response.data[i].initialOpen = "old participant" != response.data[i].name;
                response.data[i].open = response.data[i].initialOpen;
                response.data[i].button = {
                  name: response.data[i].participant_count,
                  go: function() { console.log( 'TODO' ); }
                };
                if( null !== response.data[i].rank ) {
                  response.data[i].title = 'Q' + response.data[i].rank + ': ' + response.data[i].title;
                  response.data[i].color = 'success';
                }
                self.queueList[response.data[i].id] = response.data[i];
                if( null !== response.data[i].parent_queue_id && 'qnaire' != response.data[i].name ) {
                  if( 'qnaire' == self.queueList[response.data[i].parent_queue_id].name )
                    response.data[i].parent_queue_id = eligibleQueueId;
                  self.queueList[response.data[i].parent_queue_id].branchList.push( response.data[i] );
                }
              }

              // now put all root branches into the queue tree
              for( var i = 1; i < self.queueList.length; i++ )
                if( angular.isDefined( self.queueList[i] ) && null === self.queueList[i].parent_queue_id )
                  self.queueTree.push( self.queueList[i] );
            }

            // now check for count errors
            for( var i = 1; i < self.queueList.length; i++ ) {
              if( angular.isDefined( self.queueList[i] ) && 0 < self.queueList[i].branchList.length ) {
                self.queueList[i].childTotal = 0;
                for( var c = 0; c < self.queueList[i].branchList.length; c++ )
                  self.queueList[i].childTotal += self.queueList[i].branchList[c].participant_count;

                if( self.queueList[i].childTotal != self.queueList[i].participant_count )
                  console.error(
                    'Queue "' + self.queueList[i].title +
                    '" has ' + self.queueList[i].participant_count +
                    ' participants but child queues add up to ' + self.queueList[i].childTotal +
                    ' (they should be equal)' );
              }
            }
          } );
        };
      };

      return { instance: function() { return new object(); } };
    }
  ] );

} );
