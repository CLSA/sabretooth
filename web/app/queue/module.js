define( function() {
  'use strict';

  try { var module = cenozoApp.module( 'queue', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( module, {
    identifier: { column: 'name' },
    name: {
      singular: 'queue',
      plural: 'queues',
      possessive: 'queue\'s',
      pluralPossessive: 'queues\''
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

  module.addInputGroup( null, {
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
  } );

  module.addExtraOperation( 'view', 'View Queue Tree', function( $state ) { $state.go( 'queue.tree' ); } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueList', [
    'CnQueueModelFactory',
    function( CnQueueModelFactory ) {
      return {
        templateUrl: module.url + 'list.tpl.html',
        restrict: 'E',
        scope: true,
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQueueModelFactory.root;
          $scope.model.listModel.onList( true ).then( function() {
            $scope.model.setupBreadcrumbTrail();
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueTree', [
    'CnQueueTreeFactory', 'CnSession',
    function( CnQueueTreeFactory, CnSession ) {
      return {
        templateUrl: module.url + 'tree.tpl.html',
        restrict: 'E',
        scope: true,
        controller: function( $scope ) {
          $scope.isLoading = false;
          $scope.isComplete = false;
          $scope.model = CnQueueTreeFactory.instance();
          $scope.refresh = function( updateQueueTime ) {
            $scope.model.updateQueueTime = true === updateQueueTime;
            $scope.isLoading = 0 < $scope.model.queueTree.length;
            $scope.isComplete = 0 < $scope.model.queueTree.length;
            $scope.model.onView()
              .then( function success() { CnSession.setBreadcrumbTrail( [ { title: 'Queue Tree' } ] ); } )
              .finally( function finished() { $scope.isLoading = false; $scope.isComplete = true; } );
          };
          $scope.refresh( true );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueView', [
    'CnQueueModelFactory',
    function( CnQueueModelFactory ) {
      return {
        templateUrl: module.url + 'view.tpl.html',
        restrict: 'E',
        scope: true,
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQueueModelFactory.root;
          $scope.model.viewModel.onView().then( function() {
            $scope.model.setupBreadcrumbTrail();
          } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var object = function( parentModel, root ) {
        CnBaseViewFactory.construct( this, parentModel, root );
        if( angular.isDefined( this.queueStateModel ) )
          this.queueStateModel.heading = 'Disabled Questionnaire List';

        // make sure users can't add/remove participants from queues
        if( angular.isDefined( this.participantModel ) )
          this.participantModel.enableChoose( false );
      };

      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueModelFactory', [
    'CnBaseModelFactory', 'CnQueueListFactory', 'CnQueueViewFactory',
    function( CnBaseModelFactory, CnQueueListFactory, CnQueueViewFactory ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnQueueListFactory.instance( this );
        this.viewModel = CnQueueViewFactory.instance( this, root );
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueTreeFactory', [
    '$state', 'CnSession', 'CnHttpFactory',
    function( $state, CnSession, CnHttpFactory ) {
      var object = function( root ) {
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
          self.form.isRepopulating = true;

          // blank out the button title if the tree is already built
          if( 0 < self.queueTree.length ) {
            self.queueList.forEach( function( item, index, array ) {
              if( 0 < index && angular.isDefined( item ) ) {
                array[index].participant_count = 0;
                array[index].childTotal = 0;
                array[index].button.name = '\u2026';
              }
            } );
          }

          // isRepopulating any queue repopulates them all
          CnHttpFactory.instance( { path: 'queue/1?repopulate=full' } ).get()
            .then( function success() { self.onView(); } )
            .finally( function finished() { self.form.isRepopulating = false; } );
        };

        this.onView = function() {
          // blank out the button title if the tree is already built
          if( 0 < self.queueTree.length ) {
            self.queueList.forEach( function( item, index, array ) {
              if( 0 < index && angular.isDefined( item ) ) {
                array[index].participant_count = 0;
                array[index].childTotal = 0;
                array[index].button.name = '\u2026';
              }
            } );
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
              response.data.forEach( function( item ) {
                self.form.qnaireList.push( { value: item.id, name: item.name } );
              } );
            } );
          }

          if( null == self.form.siteList && CnSession.role.allSites ) {
            CnHttpFactory.instance( {
              path: 'site',
              data: { select: { column: [ 'id', 'name' ] }, modifier: { order: 'name' } }
            } ).query().then( function( response ) {
              self.form.siteList = [ { value: undefined, name: 'All' } ];
              response.data.forEach( function( item ) {
                self.form.siteList.push( { value: item.id, name: item.name } );
              } );
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
              response.data.forEach( function( item ) {
                self.form.languageList.push( { value: item.id, name: item.name } );
              } );
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
              response.data.forEach( function( item ) {
                var queue = self.queueList[item.id];
                queue.participant_count = item.participant_count;
                queue.button.name = item.participant_count;
                queue.last_repopulation = item.last_repopulation;
              } );
            } else {
              // create an array containing all branches and add their child branches as we go
              var eligibleQueueId = null;
              var oldParticipantQueueId = null;
              response.data.forEach( function( item ) {
                // make note of certain queues
                if( null === eligibleQueueId && 'eligible' == item.name )
                  eligibleQueueId = item.id;
                if( null === oldParticipantQueueId && 'old participant' == item.name )
                  oldParticipantQueueId = item.id;

                // add all branches to the root, for now
                item.branchList = []; // will be filled in if the branch has any children
                item.initialOpen = null === oldParticipantQueueId ||
                                               oldParticipantQueueId > item.id;
                item.open = item.initialOpen;
                item.button = {
                  id: item.id,
                  name: item.participant_count,
                  go: function() { $state.go( 'queue.view', { identifier: this.id } ); }
                };
                if( null !== item.rank ) {
                  item.title = 'Q' + item.rank + ': ' + item.title;
                  item.color = 'success';
                }
                self.queueList[item.id] = item;
                if( null !== item.parent_queue_id && 'qnaire' != item.name ) {
                  if( 'qnaire' == self.queueList[item.parent_queue_id].name )
                    item.parent_queue_id = eligibleQueueId;
                  self.queueList[item.parent_queue_id].branchList.push( item );
                }
              } );

              // now put all root branches into the queue tree
              self.queueList.forEach( function( item ) {
                if( angular.isDefined( item ) && null === item.parent_queue_id ) self.queueTree.push( item );
              } );
            }

            // now check for count errors
            self.queueList.forEach( function( queue, index, array ) {
              if( 'all' == queue.name )
                self.form.lastRepopulation =
                  CnSession.formatValue( queue.last_repopulation, 'datetimesecond', false );

              if( angular.isDefined( queue ) && 0 < queue.branchList.length ) {
                var count = 0;
                queue.branchList.forEach( function( branch ) { count += branch.participant_count; } );
                array[index].childTotal = count;

                if( queue.childTotal != queue.participant_count )
                  console.error(
                    'Queue "' + queue.title +
                    '" has ' + queue.participant_count +
                    ' participants but child queues add up to ' + queue.childTotal +
                    ' (they should be equal)' );
              }
            } );
          } );
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
