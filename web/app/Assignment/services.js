define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      return { instance: function( params ) { return CnBaseAddFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentListFactory', [
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
  cnCachedProviders.factory( 'CnAssignmentViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      return { instance: function( params ) { return CnBaseViewFactory.instance( params ); } };
    }
  ] );

  /* ######################################################################################################## */
  cnCachedProviders.factory( 'CnAssignmentSingleton', [
    'CnBaseSingletonFactory', 'CnAssignmentListFactory', 'CnAssignmentAddFactory', 'CnAssignmentViewFactory'
    function( CnBaseSingletonFactory, CnAssignmentListFactory, CnAssignmentAddFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var base = CnBaseSingletonFactory.instance( {
          subject: 'assignment',
          name: {
            singular: 'assignment',
            plural: 'assignments',
            possessive: 'assignment\'s',
            pluralPossessive: 'assignments\''
          },
          cnAdd: CnAssignmentAddFactory.instance( { subject: 'assignment' } ),
          cnList: CnAssignmentListFactory.instance( { subject: 'assignment' } ),
          cnView: CnAssignmentViewFactory.instance( { subject: 'assignment' } )
        } );
        for( var p in base ) if( base.hasOwnProperty( p ) ) this[p] = base[p];
      };

      object.prototype = CnBaseSingletonFactory.prototype;
      // don't return a method to create instances, create and return the singleton
      return new object();
    }
  ] );

} );
