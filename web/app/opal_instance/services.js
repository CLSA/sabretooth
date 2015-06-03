define( [ 'app/opal_instance/module.js' ], function( module ) { 
  'use strict';

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
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
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
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnOpalInstanceListFactory.instance( this );
        this.listModel = CnOpalInstanceListFactory.instance( this );
        this.viewModel = CnOpalInstanceViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
