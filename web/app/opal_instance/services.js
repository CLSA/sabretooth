define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnOpalInstanceAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnOpalInstanceListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( params ) {
        var base = CnBaseListFactory.instance( params );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];

        ////////////////////////////////////
        // factory customizations start here
        this.columnList = {
          id: { title: 'ID' }
        };
        this.order = { column: 'id', reverse: false };
        // factory customizations end here
        //////////////////////////////////

        cnCopyParams( this, params );
      };

      object.prototype = CnBaseListFactory.prototype;
      return { instance: function( params ) { return new object( undefined === params ? {} : params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnOpalInstanceViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnOpalInstanceSingleton', [
    'CnBaseSingletonFactory', 'CnOpalInstanceListFactory', 'CnOpalInstanceAddFactory', 'CnOpalInstanceViewFactory'
    function( CnBaseSingletonFactory, CnOpalInstanceListFactory, CnOpalInstanceAddFactory, CnOpalInstanceViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'opal_instance',
          name: {
            singular: 'opal instance',
            plural: 'opal instances',
            possessive: 'opal instance\'s',
            pluralPossessive: 'opal instances\''
          },
          cnAdd: CnOpalInstanceAddFactory.instance( { subject: 'opal_instance' } ),
          cnList: CnOpalInstanceListFactory.instance( { subject: 'opal_instance' } ),
          cnView: CnOpalInstanceViewFactory.instance( { subject: 'opal_instance' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
