define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQueueAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQueueListFactory', [
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
  cnCachedProviders.factory( 'CnQueueViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQueueSingleton', [
    'CnBaseSingletonFactory', 'CnQueueListFactory', 'CnQueueAddFactory', 'CnQueueViewFactory'
    function( CnBaseSingletonFactory, CnQueueListFactory, CnQueueAddFactory, CnQueueViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'queue',
          name: {
            singular: 'queue',
            plural: 'queues',
            possessive: 'queue\'s',
            pluralPossessive: 'queues\''
          },
          cnAdd: CnQueueAddFactory.instance( { subject: 'queue' } ),
          cnList: CnQueueListFactory.instance( { subject: 'queue' } ),
          cnView: CnQueueViewFactory.instance( { subject: 'queue' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
