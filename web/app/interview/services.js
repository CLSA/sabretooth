define( [ 'app/interview/module.js' ], function( module ) {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } 
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewAddFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    function( CnBaseModelFactory, CnInterviewAddFactory, CnInterviewListFactory, CnInterviewViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnInterviewListFactory.instance( this );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
