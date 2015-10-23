define( cenozo.getDependencyList( 'cedar_instance' ), function() {
  'use strict';

  var module = cenozoApp.module( 'cedar_instance' );
  angular.extend( module, {
    identifier: {}, // standard
    name: {
      singular: 'cedar instance',
      plural: 'cedar instances',
      possessive: 'cedar instance\'s',
      pluralPossessive: 'cedar instances\'',
      friendlyColumn: 'username'
    },
    inputList: {
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

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceAddCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.record = {};
      $scope.model.addModel.onNew( $scope.record ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'add' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceListCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.model.listModel.onList( true ).then( function() {
        $scope.model.setupBreadcrumbTrail( 'list' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.controller( 'CedarInstanceViewCtrl', [
    '$scope', 'CnCedarInstanceModelFactory', 'CnSession',
    function( $scope, CnCedarInstanceModelFactory, CnSession ) {
      $scope.model = CnCedarInstanceModelFactory.root;
      $scope.model.viewModel.onView().then( function() {
        $scope.model.setupBreadcrumbTrail( 'view' );
      } ).catch( CnSession.errorHandler );
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCedarInstanceAdd', function () {
    return {
      templateUrl: 'app/cedar_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCedarInstanceView', function () {
    return {
      templateUrl: 'app/cedar_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCedarInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } 
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCedarInstanceListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCedarInstanceViewFactory',
    cenozo.getViewModelInjectionList( 'cedar_instance' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel, args ); }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCedarInstanceModelFactory', [
    'CnBaseModelFactory',
    'CnCedarInstanceAddFactory', 'CnCedarInstanceListFactory', 'CnCedarInstanceViewFactory',
    function( CnBaseModelFactory,
              CnCedarInstanceAddFactory, CnCedarInstanceListFactory, CnCedarInstanceViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnCedarInstanceAddFactory.instance( this );
        this.listModel = CnCedarInstanceListFactory.instance( this );
        this.viewModel = CnCedarInstanceViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
