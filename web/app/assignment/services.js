define( [ 'app/assignment/module.js' ], function( module ) {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnAssignmentModelFactory', [
    'CnBaseModelFactory',
    'CnAssignmentListFactory', 'CnAssignmentViewFactory',
    function( CnBaseModelFactory,
              CnAssignmentListFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnAssignmentListFactory.instance( this );
        this.viewModel = CnAssignmentViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
