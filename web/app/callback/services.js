define( cenozo.getServicesIncludeList( 'callback' ), function( module ) { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCallbackAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } 
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCallbackListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCallbackViewFactory',
    cenozo.getListModelInjectionList( 'callback' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, args );

        this.onView = function() {
          return this.viewRecord().then( function() {
            var upcoming = moment().isBefore( self.record.datetime );
            parentModel.enableDelete( upcoming );
            parentModel.enableEdit( upcoming );
          } );
        };
      }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnCallbackModelFactory', [
    'CnBaseModelFactory', 'CnCallbackAddFactory', 'CnCallbackListFactory', 'CnCallbackViewFactory',
    'CnHttpFactory',
    function( CnBaseModelFactory, CnCallbackAddFactory, CnCallbackListFactory, CnCallbackViewFactory,
              CnHttpFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnCallbackAddFactory.instance( this );
        this.listModel = CnCallbackListFactory.instance( this );
        this.viewModel = CnCallbackViewFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {
            var parent = self.getParentIdentifier();
            
            if( angular.isDefined( parent.subject ) && angular.isDefined( parent.identifier ) ) {
              return CnHttpFactory.instance( {
                path: [ parent.subject, parent.identifier ].join( '/' ),
                data: { select: { column: { column: 'participant_id' } } }
              } ).query().then( function( response ) {
                return CnHttpFactory.instance( {
                  path: ['participant', response.data.participant_id, 'phone' ].join( '/' ),
                  data: {
                    select: { column: [ 'id', 'rank', 'type', 'number' ] },
                    modifier: { order: { rank: false } }
                  }
                } ).query().then( function( response ) {
                  self.metadata.columnList.phone_id.enumList = [];
                  for( var i = 0; i < response.data.length; i++ ) {
                    self.metadata.columnList.phone_id.enumList.push( {
                      value: response.data[i].id,
                      name: '(' + response.data[i].rank + ') ' + response.data[i].type + ': ' + response.data[i].number
                    } );
                  }
                } ).then( function() {
                  self.metadata.loadingCount--;
                } );
              } );
            } else self.metadata.loadingCount--;
          } );
        };
      };

      return {
        root: new object(),
        instance: function() { return new object(); }
      };
    }
  ] );

} );
