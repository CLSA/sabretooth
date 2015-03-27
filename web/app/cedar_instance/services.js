define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
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
      return { instance: function( params ) { return new object( undefined === params ? {} : params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnCedarInstanceSingleton', [
    'CnBaseSingletonFactory', 'CnCedarInstanceListFactory', 'CnCedarInstanceAddFactory', 'CnCedarInstanceViewFactory',
    function( CnBaseSingletonFactory, CnCedarInstanceListFactory, CnCedarInstanceAddFactory, CnCedarInstanceViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'cedar_instance',
          name: {
            singular: 'cedar instance',
            plural: 'cedar instances',
            possessive: 'cedar instance\'s',
            pluralPossessive: 'cedar instances\''
          },
          cnAdd: CnCedarInstanceAddFactory.instance( { subject: 'cedar_instance' } ),
          cnList: CnCedarInstanceListFactory.instance( { subject: 'cedar_instance' } ),
          cnView: CnCedarInstanceViewFactory.instance( { subject: 'cedar_instance' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
