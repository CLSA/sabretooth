define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQnaireAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQnaireListFactory', [
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
  cnCachedProviders.factory( 'CnQnaireViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnQnaireSingleton', [
    'CnBaseSingletonFactory', 'CnQnaireListFactory', 'CnQnaireAddFactory', 'CnQnaireViewFactory'
    function( CnBaseSingletonFactory, CnQnaireListFactory, CnQnaireAddFactory, CnQnaireViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'qnaire',
          name: {
            singular: 'questionnaire',
            plural: 'questionnaires',
            possessive: 'questionnaire\'s',
            pluralPossessive: 'questionnaires\''
          },
          cnAdd: CnQnaireAddFactory.instance( { subject: 'qnaire' } ),
          cnList: CnQnaireListFactory.instance( { subject: 'qnaire' } ),
          cnView: CnQnaireViewFactory.instance( { subject: 'qnaire' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
