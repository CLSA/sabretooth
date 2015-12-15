define( function() {
  'use strict';

  try { cenozoApp.module( 'qnaire', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'qnaire' ), {
    identifier: { column: 'rank' },
    name: {
      singular: 'questionnaire',
      plural: 'questionnaires',
      possessive: 'questionnaire\'s',
      pluralPossessive: 'questionnaires\''
    },
    columnList: {
      name: {
        column: 'script.name',
        title: 'Name'
      },
      rank: {
        title: 'Rank',
        type: 'rank'
      },
      delay: {
        title: 'Delay',
        type: 'number'
      }
    },
    defaultOrder: {
      column: 'rank',
      reverse: false
    }
  } );

  cenozoApp.module( 'qnaire' ).addInputGroup( null, {
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    script_id: {
      title: 'Script',
      type: 'enum',
      noedit: true,
      help: 'Only scripts which are marked as non-repeatable may be used as a questionnaire.'
    },
    delay: {
      title: 'Delay (weeks)',
      type: 'string',
      format: 'integer',
      minValue: 0
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireAddCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireListCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'QnaireViewCtrl', [
    '$scope', 'CnQnaireModelFactory',
    function( $scope, CnQnaireModelFactory ) {
      $scope.model = CnQnaireModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireAdd', function() {
    return {
      templateUrl: 'app/qnaire/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireView', function() {
    return {
      templateUrl: 'app/qnaire/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) {
        CnBaseViewFactory.construct( this, parentModel, root );
        if( angular.isDefined( this.eventTypeModel ) )
          this.eventTypeModel.heading = 'Required To Begin Event List';
        if( angular.isDefined( this.queueStateModel ) )
          this.queueStateModel.heading = 'Disabled Queue List';
        if( angular.isDefined( this.quotaModel ) )
          this.quotaModel.heading = 'Disabled Quota List';
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireModelFactory', [
    'CnBaseModelFactory', 'CnQnaireAddFactory', 'CnQnaireListFactory', 'CnQnaireViewFactory',
    'CnSession', 'CnHttpFactory', '$q',
    function( CnBaseModelFactory, CnQnaireAddFactory, CnQnaireListFactory, CnQnaireViewFactory,
              CnSession, CnHttpFactory, $q ) {
      var object = function( root ) {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'qnaire' ) );
        this.addModel = CnQnaireAddFactory.instance( this );
        this.listModel = CnQnaireListFactory.instance( this );
        this.viewModel = CnQnaireViewFactory.instance( this, root );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return $q.all( [

            this.loadMetadata(),

            CnHttpFactory.instance( {
              path: 'application/' + CnSession.application.id + '/script',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: {
                  where: [ { column: 'repeated', operator: '=', value: false } ],
                  order: 'name'
                }
              }
            } ).query().then( function( response ) {
              self.metadata.columnList.script_id.enumList = [];
              response.data.forEach( function( item ) {
                self.metadata.columnList.script_id.enumList.push( { value: item.id, name: item.name } );
              } );
            } )

          ] ).finally( function finished() { self.metadata.loadingCount--; } );
        };
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
