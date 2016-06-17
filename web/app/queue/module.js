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

  module.addInputGroup( '', {
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

  if( angular.isDefined( module.actions.tree ) ) {
    module.addExtraOperation( 'view', {
      title: 'View Queue Tree',
      operation: function( $state, model ) {
        // if the queue's participant list has restrictions on qnaire, site or language then apply them
        var restrictList = model.viewModel.participantModel.listModel.columnRestrictLists;
        var params = {};
        if( angular.isDefined( restrictList.qnaire ) ) {
          var restrict = restrictList.qnaire.findByProperty( 'test', '<=>' );
          params.qnaire = restrict.value;
        }
        if( angular.isDefined( restrictList.site ) ) {
          var restrict = restrictList.site.findByProperty( 'test', '<=>' );
          params.site = restrict.value;
        }
        if( angular.isDefined( restrictList.language ) ) {
          var restrict = restrictList.language.findByProperty( 'test', '<=>' );
          params.language = restrict.value;
        }

        $state.go( 'queue.tree', params );
      }
    } );
  }

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueList', [
    'CnQueueModelFactory',
    function( CnQueueModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'list.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQueueModelFactory.root;
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueTree', [
    'CnQueueTreeFactory', 'CnSession',
    function( CnQueueTreeFactory, CnSession ) {
      return {
        templateUrl: module.getFileUrl( 'tree.tpl.html' ),
        restrict: 'E',
        controller: function( $scope ) {
          $scope.model = CnQueueTreeFactory.instance();
          $scope.isLoading = true;
          $scope.model.onView( true )
            .then( function success() { CnSession.setBreadcrumbTrail( [ { title: 'Queue Tree' } ] ); } )
            .finally( function finished() { $scope.isLoading = false; } );
        }
      };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueView', [
    'CnQueueModelFactory',
    function( CnQueueModelFactory ) {
      return {
        templateUrl: module.getFileUrl( 'view.tpl.html' ),
        restrict: 'E',
        scope: { model: '=?' },
        controller: function( $scope ) {
          if( angular.isUndefined( $scope.model ) ) $scope.model = CnQueueModelFactory.root;
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
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // only ranked queues have queue states
        this.afterView( function() {
          if( angular.isDefined( self.queueStateModel ) ) self.queueStateModel.show = null != self.record.rank;
        } );

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.participantModel ) ) {
            // map queue-view query parameters to participant-list
            self.participantModel.queryParameterSubject = 'queue';

            // override model functions
            self.participantModel.getServiceData = function( type, columnRestrictList ) {
              var data = this.$$getServiceData( type, columnRestrictList );
              if( 'list' == type ) data.repopulate = true;
              return data;
            };

            // add additional columns to the model
            self.participantModel.addColumn( 'qnaire', { title: 'Questionnaire', column: 'script.name' }, 0 );
            self.participantModel.addColumn( 'language', { title: 'Language', column: 'language.name' }, 1 );

            // make sure users can't add/remove participants from queues
            self.participantModel.enableChoose( false );
          }

          if( angular.isDefined( self.queueStateModel ) ) {
            // set a custom heading for the queue state list model
            self.queueStateModel.listModel.heading = 'Disabled Questionnaire List';

            // make sure users can edit the queue restriction list despite the queue being read-only
            var queueStateModule = cenozoApp.module( 'queue_state' );
            self.queueStateModel.show = false;
            self.queueStateModel.enableAdd( angular.isDefined( queueStateModule.actions.add ) );
            self.queueStateModel.enableDelete( angular.isDefined( queueStateModule.actions.delete ) );
          }
        } );
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
    '$q', '$state', 'CnQueueModelFactory', 'CnSession', 'CnHttpFactory',
    function( $q, $state, CnQueueModelFactory, CnSession, CnHttpFactory ) {
      var object = function( root ) {
        var self = this;
        this.queueList = []; // one-dimensional list for manipulation
        this.queueTree = []; // multi-dimensional tree for display
        this.queueModel = CnQueueModelFactory.root;

        this.form = {
          canRepopulate: 3 <= CnSession.role.tier,
          lastRepopulation: null,
          isRepopulating: false,
          qnaire_id: undefined,
          qnaireList: [],
          site_id: undefined,
          siteList: [],
          language_id: undefined,
          languageList: []
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

        this.refreshState = function() {
          var qnaireName = undefined;
          if( angular.isDefined( this.form.qnaire_id ) ) {
            var qnaire = this.form.qnaireList.findByProperty( 'value', this.form.qnaire_id );
            if( qnaire ) qnaireName = qnaire.name;
          }
          this.queueModel.setQueryParameter( 'qnaire', qnaireName );

          var siteName = undefined;
          if( angular.isDefined( this.form.site_id ) ) {
            var site = this.form.siteList.findByProperty( 'value', this.form.site_id );
            if( site ) siteName = site.name;
          }
          this.queueModel.setQueryParameter( 'site', siteName );

          var languageName = undefined;
          if( angular.isDefined( this.form.language_id ) ) {
            var language = this.form.languageList.findByProperty( 'value', this.form.language_id );
            if( language ) languageName = language.name;
          }
          this.queueModel.setQueryParameter( 'language', languageName );

          return $q.all( [
            this.queueModel.reloadState( false, true ),
            this.onView( false )
          ] );
        };

        this.onView = function( updateQueue ) {
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

          var promiseList = [];

          if( 0 == self.form.qnaireList.length ) {
            promiseList.push(
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
              } )
            );
          }

          if( 0 == self.form.siteList.length && CnSession.role.allSites ) {
            promiseList.push(
              CnHttpFactory.instance( {
                path: 'site',
                data: { select: { column: [ 'id', 'name' ] }, modifier: { order: 'name' } }
              } ).query().then( function( response ) {
                self.form.siteList = [ { value: undefined, name: 'All' } ];
                response.data.forEach( function( item ) {
                  self.form.siteList.push( { value: item.id, name: item.name } );
                } );
              } )
            );
          }

          if( 0 == self.form.languageList.length ) {
            promiseList.push(
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
              } )
            );
          }

          return $q.all( promiseList ).then( function() {
            // determine the qnaire, site and language from the query parameters
            var qnaireName = self.queueModel.getQueryParameter( 'qnaire' );
            if( angular.isDefined( qnaireName ) ) {
              var qnaire = self.form.qnaireList.findByProperty( 'name', qnaireName );
              self.form.qnaire_id = qnaire ? qnaire.value : undefined;
            }

            var siteName = self.queueModel.getQueryParameter( 'site' );
            if( angular.isDefined( siteName ) ) {
              var site = self.form.siteList.findByProperty( 'name', siteName );
              self.form.site_id = site ? site.value : undefined;
            }

            var languageName = self.queueModel.getQueryParameter( 'language' );
            if( angular.isDefined( languageName ) ) {
              var language = self.form.languageList.findByProperty( 'name', languageName );
              self.form.language_id = language ? language.value : undefined;
            }

            // build a where statement based on the qnaire, site and language parameters
            var whereList = [];
            if( angular.isDefined( self.form.qnaire_id ) )
              whereList.push( { column: 'qnaire_id', operator: '=', value: self.form.qnaire_id } );
            if( angular.isDefined( self.form.site_id ) )
              whereList.push( { column: 'site_id', operator: '=', value: self.form.site_id } );
            if( angular.isDefined( self.form.language_id ) )
              whereList.push( { column: 'language_id', operator: '=', value: self.form.language_id } );

            return CnHttpFactory.instance( {
              path: 'queue?full=1' + ( updateQueue ? '&repopulate=time' : '' ),
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
                    go: function() {
                      var restrict = {};
                      var qnaireName = self.queueModel.getQueryParameter( 'qnaire' );
                      if( qnaireName ) restrict.qnaire = [ { test: "<=>", value: qnaireName } ];
                      var siteName = self.queueModel.getQueryParameter( 'site' );
                      if( siteName ) restrict.site = [ { test: "<=>", value: siteName } ];
                      var languageName = self.queueModel.getQueryParameter( 'language' );
                      if( languageName ) restrict.language = [ { test: "<=>", value: languageName } ];

                      var params = { identifier: this.id };
                      if( 0 < Object.keys( restrict ).length ) params.restrict = angular.toJson( restrict );

                      $state.go( 'queue.view', params );
                    }
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
          } );
        };
      };

      return { instance: function() { return new object( false ); } };
    }
  ] );

} );
