define( cenozo.getDependencyList( 'queue' ), function() {
  'use strict';

  var module = cenozoApp.module( 'queue' );
  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'queue',
      plural: 'queues',
      possessive: 'queue\'s',
      pluralPossessive: 'queues\''
    },
    inputList: {
      rank: {
        title: 'Rank',
        type: 'rank',
        constant: true
      },
      name: {
        title: 'Name',
        type: 'string',
        constant: true
      },
      title: {
        title: 'Title',
        type: 'string',
        constant: true
      },
      description: {
        title: 'Description',
        type: 'text',
        constant: true
      }
    },
    columnList: {
      rank: {
        title: 'Rank',
        type: 'rank'
      },
      name: { title: 'Name' },
      participant_count: {
        title: 'Participants',
        type: 'number'
      }
    },
    defaultOrder: {
      column: 'rank',
      reverse: false
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueListCtrl', [
    '$scope', 'CnQueueModelFactory', 'CnSession',
    function( $scope, CnQueueModelFactory, CnSession ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueViewCtrl', [
    '$scope', 'CnQueueModelFactory', 'CnSession',
    function( $scope, CnQueueModelFactory, CnSession ) {
      $scope.model = CnQueueModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueTreeCtrl', [
    '$scope', 'CnQueueTreeFactory', 'CnSession',
    function( $scope, CnQueueTreeFactory, CnSession ) {
      $scope.isLoading = false;
      $scope.isComplete = false;
      $scope.model = CnQueueTreeFactory.instance();
      $scope.refresh = function( updateQueueTime ) {
        $scope.model.updateQueueTime = true === updateQueueTime;
        $scope.isLoading = 0 < $scope.model.queueTree.length;
        $scope.isComplete = 0 < $scope.model.queueTree.length;
        $scope.model.onView().then( function() {
          CnSession.setBreadcrumbTrail( [ { title: 'Queue Tree' } ] );
          $scope.isLoading = false; $scope.isComplete = true;
        } ).catch( CnSession.errorHandler );
      };
      $scope.refresh();
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueView', function () {
    return {
      templateUrl: 'app/queue/view.tpl.html',
      restrict: 'E'
    };
  } );

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
    cenozo.getViewModelInjectionList( 'queue' ).concat( [ '$state', function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var $state = args[args.length-1];
      var object = function( parentModel ) {
        CnBaseViewFactory.construct( this, parentModel, args );

        // make sure users can't add/remove participants from queues
        this.participantModel.enableChoose( false );
        
        // add operations
        this.operationList = [ {
          name: 'View Queue Tree',
          execute: function() { $state.go( 'queue.tree' ); }
        } ];
      };

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
    '$state', 'CnSession', 'CnHttpFactory',
    function( $state, CnSession, CnHttpFactory ) {
      var object = function() {
        var self = this;
        this.queueList = []; // one-dimensional list for manipulation
        this.queueTree = []; // multi-dimensional tree for display
        this.updateQueueTime = true; // repopulate the time queue the first time we load

        this.form = {
          canRepopulate: 3 <= CnSession.role.tier,
          lastRepopulation: null,
          isRepopulating: false,
          qnaire_id: undefined,
          qnaireList: null,
          site_id: undefined,
          siteList: null,
          language_id: undefined,
          languageList: null
        };

        this.repopulate = function() {
          this.form.isRepopulating = true;

          // blank out the button title if the tree is already built
          if( 0 < this.queueTree.length ) {
            for( var i = 1; i < this.queueList.length; i++ ) {
              if( angular.isDefined( this.queueList[i] ) ) {
                this.queueList[i].participant_count = 0;
                this.queueList[i].childTotal = 0;
                this.queueList[i].button.name = '\u2026';
              }
            }
          }

          // isRepopulating any queue repopulates them all
          CnHttpFactory.instance( { path: 'queue/1?repopulate=full' } ).get().then( function() {
            self.onView().then( function() { self.form.isRepopulating = false; } );
          } );
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
              data: {
                select: { column: [ 'id', { table: 'script', column: 'name' } ] },
                modifier: { order: 'rank' }
              }
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
          if( angular.isDefined( self.form.language_id ) )
            whereList.push( { column: 'language_id', operator: '=', value: self.form.language_id } );

          return CnHttpFactory.instance( {
            path: 'queue?full=1' + ( self.updateQueueTime ? '&repopulate=time' : '' ),
            data: {
              modifier: {
                order: 'id',
                where: whereList
              },
              select: { column: [ "id", "parent_queue_id", "rank", "name", "title", "participant_count" ] }
            }
          } ).query().then( function( response ) {
            if( self.updateQueueTime ) self.updateQueueTime = false;
            if( 0 < self.queueTree.length ) {
              // don't rebuild the queue, just update the participant totals
              for( var i = 0; i < response.data.length; i++ ) {
                var queue = self.queueList[response.data[i].id];
                queue.participant_count = response.data[i].participant_count;
                queue.button.name = response.data[i].participant_count;
                queue.last_repopulation = response.data[i].last_repopulation;
              }
            } else {
              // create an array containing all branches and add their child branches as we go
              var eligibleQueueId = null;
              var oldParticipantQueueId = null;
              for( var i = 0; i < response.data.length; i++ ) {
                // make note of certain queues
                if( null === eligibleQueueId && 'eligible' == response.data[i].name )
                  eligibleQueueId = response.data[i].id;
                if( null === oldParticipantQueueId && 'old participant' == response.data[i].name )
                  oldParticipantQueueId = response.data[i].id;

                // add all branches to the root, for now
                response.data[i].branchList = []; // will be filled in if the branch has any children
                response.data[i].initialOpen = null === oldParticipantQueueId ||
                                               oldParticipantQueueId > response.data[i].id;
                response.data[i].open = response.data[i].initialOpen;
                response.data[i].button = {
                  id: response.data[i].id,
                  name: response.data[i].participant_count,
                  go: function() { $state.go( 'queue.view', { identifier: this.id } ); }
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
              if( 'all' == self.queueList[i].name )
                self.form.lastRepopulation =
                  CnSession.formatValue( self.queueList[i].last_repopulation, 'datetimesecond', false );

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
