define( [], function() {

  'use strict';

  var moduleSubject = 'cedar_instance';
  var moduleNames = {
    singular: 'cedar instance',
    plural: 'cedar instances',
    possessive: 'cedar instance\'s',
    pluralPossessive: 'cedar instances\''
  };

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return CnBaseAddFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( params ) {
        var base = CnBaseListFactory.instance( params );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];

        ////////////////////////////////////
        // factory customizations start here
        this.columnList = {
          name: {
            column: 'user.name',
            title: 'Name'
          },
          active: {
            column: 'user.active',
            title: 'Active',
            filter: 'cnYesNo'
          },
          last_datetime: {
            title: 'Last Activity',
            filter: 'date:"MMM d, y HH:mm"'
          }
        };
        this.order = { column: 'name', reverse: false };
        // factory customizations end here
        //////////////////////////////////

        cnCopyParams( this, params );
      };

      object.prototype = CnBaseListFactory.prototype;
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return new object( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) {
        if( undefined === params ) params = {};
        params.subject = moduleSubject;
        params.name = moduleNames;
        return CnBaseViewFactory.instance( params );
      } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceSingleton', [
    'CnBaseSingletonFactory', 'CnCedarInstanceListFactory', 'CnCedarInstanceAddFactory', 'CnCedarInstanceViewFactory',
    function( CnBaseSingletonFactory, CnCedarInstanceListFactory, CnCedarInstanceAddFactory, CnCedarInstanceViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: moduleSubject,
          name: moduleNames,
          cnAdd: CnCedarInstanceAddFactory.instance(),
          cnList: CnCedarInstanceListFactory.instance(),
          cnView: CnCedarInstanceViewFactory.instance()
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
