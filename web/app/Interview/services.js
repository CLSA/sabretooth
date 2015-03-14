define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewListFactory', [
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
  cnCachedProviders.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnInterviewSingleton', [
    'CnBaseSingletonFactory', 'CnInterviewListFactory', 'CnInterviewAddFactory', 'CnInterviewViewFactory', 'CnHttpFactory',
    function( CnBaseSingletonFactory, CnInterviewListFactory, CnInterviewAddFactory, CnInterviewViewFactory, CnHttpFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'interview',
          name: {
            singular: 'interview',
            plural: 'interviews',
            possessive: 'interview\'s',
            pluralPossessive: 'interviews\''
          },
          cnAdd: CnInterviewAddFactory.instance( { subject: 'interview' } ),
          cnList: CnInterviewListFactory.instance( { subject: 'interview' } ),
          cnView: CnInterviewViewFactory.instance( { subject: 'interview' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
