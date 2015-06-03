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
    '$state', 'CnBaseModelFactory', 'CnAssignmentListFactory', 'CnAssignmentViewFactory',
    function( $state, CnBaseModelFactory, CnAssignmentListFactory, CnAssignmentViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnAssignmentListFactory.instance( this );
        this.viewModel = CnAssignmentViewFactory.instance( this );

        // override parent method to always go directly to the root assignment state
        this.transitionToAddState = function() {
          $state.go( this.subject + '.add' );
        };
        this.transitionToViewState = function( record ) {
          $state.go( this.subject + '.view', { identifier: record.getIdentifier() } );
        };
        this.transitionToLastState = function() {
          var stateName = $state.current.name;
          var action = stateName.substring( stateName.lastIndexOf( '.' ) + 1 );
          if( 'add' == action || 'view' == action ) {
            console.log( this.viewModel.record );
            //$state.go( 'interview.view', { identifier: record.
          } else { // sub-view, return to parent view
            $state.go( '^.view', { identifier: $stateParams.parentIdentifier } );
          }
        };
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
