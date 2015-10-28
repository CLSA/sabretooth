define( cenozo.getDependencyList( 'queue_state' ), function() {
  'use strict';

  var module = cenozoApp.module( 'queue_state' );
  angular.extend( module, {
    identifier: {
      parent: [ {
        subject: 'queue',
        column: 'queue.name'
      }, {
        subject: 'site',
        column: 'site.name'
      }, {
        subject: 'qnaire',
        column: 'qnaire.rank'
      } ]
    },
    name: {
      singular: 'queue restriction',
      plural: 'queue restrictions',
      possessive: 'queue restriction\'s',
      pluralPossessive: 'queue restrictions\''
    },
    columnList: {
      queue: {
        column: 'queue.title',
        title: 'Queue'
      },
      site: {
        column: 'site.name',
        title: 'Site'
      },
      qnaire: {
        column: 'script.name',
        title: 'Questionnaire'
      }
    },
    defaultOrder: {
      column: 'site',
      reverse: false
    }
  } );

  module.addInputGroup( null, {
    queue_id: {
      title: 'Queue',
      type: 'enum'
    },
    site_id: {
      title: 'Site',
      type: 'enum'
    },
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueStateAddCtrl', [
    '$scope', 'CnQueueStateModelFactory', 'CnSession',
    function( $scope, CnQueueStateModelFactory, CnSession ) {
      $scope.model = CnQueueStateModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QueueStateListCtrl', [
    '$scope', 'CnQueueStateModelFactory', 'CnSession',
    function( $scope, CnQueueStateModelFactory, CnSession ) {
      $scope.model = CnQueueStateModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQueueStateAdd', function () {
    return {
      templateUrl: 'app/queue_state/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateModelFactory', [
    'CnBaseModelFactory', 'CnQueueStateListFactory', 'CnQueueStateAddFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnQueueStateListFactory, CnQueueStateAddFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnQueueStateAddFactory.instance( this );
        this.listModel = CnQueueStateListFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {

          var promiseList = [
              CnHttpFactory.instance( {
                path: 'queue',
                data: {
                  select: { column: [ 'id', 'title' ] },
                  modifier: { order: { name: false } }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.queue_id.enumList = [];
                for( var i = 0; i < response.data.length; i++ ) {
                  self.metadata.columnList.queue_id.enumList.push( {
                    value: response.data[i].id,
                    name: response.data[i].title
                  } );
                }
              } ),

              CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', { table: 'script', column: 'name' } ] },
                  modifier: { order: 'rank' }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.qnaire_id.enumList = [];
                for( var i = 0; i < response.data.length; i++ ) {
                  self.metadata.columnList.qnaire_id.enumList.push( {
                    value: response.data[i].id,
                    name: response.data[i].name
                  } );
                }
              } )
            ];

            if( !CnSession.role.all_sites ) {
              self.metadata.columnList.site_id.enumList = [ {
                value: CnSession.site.id,
                name: CnSession.site.name
              } ];
            } else {
              promiseList.push(
                CnHttpFactory.instance( {
                  path: 'site',
                  data: {
                    select: { column: [ 'id', 'name' ] },
                    modifier: { order: { name: false } }
                  }
                } ).query().then( function success( response ) {
                  self.metadata.columnList.site_id.enumList = [];
                  for( var i = 0; i < response.data.length; i++ ) {
                    self.metadata.columnList.site_id.enumList.push( {
                      value: response.data[i].id,
                      name: response.data[i].name
                    } );
                  }
                } )
              );
            }

            return $q.all( promiseList ).then( function() { self.metadata.loadingCount--; } );
          } );
        };
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
