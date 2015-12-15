define( function() {
  'use strict';

  try { cenozoApp.module( 'opal_instance', true ); } catch( err ) { console.warn( err ); return; }
  angular.extend( cenozoApp.module( 'opal_instance' ), {
    identifier: {}, // standard
    name: {
      singular: 'opal instance',
      plural: 'opal instances',
      possessive: 'opal instance\'s',
      pluralPossessive: 'opal instances\'',
      friendlyColumn: 'username'
    },
    columnList: {
      name: {
        column: 'user.name',
        title: 'Name'
      },
      active: {
        column: 'user.active',
        title: 'Active',
        type: 'boolean'
      },
      last_access_datetime: {
        title: 'Last Activity',
        type: 'datetime'
      }
    },
    defaultOrder: {
      column: 'name',
      reverse: false
    }
  } );

  cenozoApp.module( 'opal_instance' ).addInputGroup( null, {
    active: {
      title: 'Active',
      type: 'boolean'
    },
    username: {
      title: 'Username',
      type: 'string'
    },
    password: {
      title: 'Password',
      type: 'string',
      regex: '^((?!(password)).){8,}$', // length >= 8 and can't have "password"
      noview: true,
      help: 'Passwords must be at least 8 characters long and cannot contain the word "password"'
    }
  } );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceAddCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceListCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'OpalInstanceViewCtrl', [
    '$scope', 'CnOpalInstanceModelFactory',
    function( $scope, CnOpalInstanceModelFactory ) {
      $scope.model = CnOpalInstanceModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnOpalInstanceAdd', function() {
    return {
      templateUrl: 'app/opal_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnOpalInstanceView', function() {
    return {
      templateUrl: 'app/opal_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnOpalInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnOpalInstanceListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnOpalInstanceViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel, root ) { CnBaseViewFactory.construct( this, parentModel, root ); }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnOpalInstanceModelFactory', [
    'CnBaseModelFactory',
    'CnOpalInstanceAddFactory', 'CnOpalInstanceListFactory', 'CnOpalInstanceViewFactory',
    function( CnBaseModelFactory,
              CnOpalInstanceAddFactory, CnOpalInstanceListFactory, CnOpalInstanceViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, cenozoApp.module( 'opal_instance' ) );
        this.addModel = CnOpalInstanceAddFactory.instance( this );
        this.listModel = CnOpalInstanceListFactory.instance( this );
        this.viewModel = CnOpalInstanceViewFactory.instance( this );
      };

      return {
        root: new object( true ),
        instance: function() { return new object( false ); }
      };
    }
  ] );

} );
