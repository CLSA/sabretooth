define( cenozo.getServicesIncludeList( 'queue' ), function( module ) { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueViewFactory',
    cenozo.getListModelInjectionList( 'queue' ).concat( [ 'CnHttpFactory', function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var CnHttpFactory = args[args.length-1];
      var object = function( parentModel ) {
        CnBaseViewFactory.construct( this, parentModel, args );

        // make sure users can't add/remove participants from queues
        this.participantModel.enableChoose( false );

        // add operations
        var self = this;
        if( true ) {
          this.onView = function() {
            return this.viewRecord().then( function() {
              self.operationList = [ {
                name: 'Repopulate',
                execute: function() {
                  CnHttpFactory.instance( {
                    path: 'queue/' + self.record.id + '?repopulate=true'
                  } ).patch();
                }
              } ];
            } );
          };
        }
      }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } ] )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueModelFactory', [
    'CnBaseModelFactory', 'CnQueueListFactory', 'CnQueueViewFactory',
    function( CnBaseModelFactory, CnQueueListFactory, CnQueueViewFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnQueueListFactory.instance( this );
        this.viewModel = CnQueueViewFactory.instance( this );
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
