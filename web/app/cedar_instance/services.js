define( cenozo.getServicesIncludeList( 'cedar_instance' ), function( module ) { 
  'use strict';

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
    cenozo.getListModelInjectionList( 'cedar_instance' ).concat( function() {
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
