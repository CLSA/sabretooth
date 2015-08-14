define( cenozo.getServicesIncludeList( 'queue_state' ), function( module ) {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQueueStateModelFactory', [
    'CnBaseModelFactory', 'CnQueueStateListFactory', 'CnQueueStateAddFactory', 'CnSession', 'CnHttpFactory',
    function( CnBaseModelFactory, CnQueueStateListFactory, CnQueueStateAddFactory, CnSession, CnHttpFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnQueueStateAddFactory.instance( this );
        this.listModel = CnQueueStateListFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'queue',
              data: {
                select: { column: [ 'id', 'title' ] },
                modifier: { order: { name: false } }
              }
            } ).query().then( function success( response ) {
              self.metadata.columnList.queue_id.enumList = [];
              for( var i = 0; i < response.data.length; i++ ) {
                self.metadata.columnList.queue_id.enumList.push( {
                  value: response.data[i].id,
                  name: response.data[i].title
                } );
              }
            } ).then( function() {
              if( !CnSession.role.all_sites ) {
                self.metadata.columnList.site_id.enumList = [ {
                  value: CnSession.site.id,
                  name: CnSession.site.name
                } ];
              } else {
                CnHttpFactory.instance( {
                  path: 'site',
                  data: {
                    select: { column: [ 'id', 'name' ] },
                    modifier: { order: { name: false } }
                  }
                } ).query().then( function success( response ) {
                  self.metadata.columnList.site_id.enumList = [];
                  for( var i = 0; i < response.data.length; i++ ) {
                    self.metadata.columnList.site_id.enumList.push( {
                      value: response.data[i].id,
                      name: response.data[i].name
                    } );
                  }
                } );
              }
            } ).then( function() {
              return CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: { name: false } }
                }
              } ).query().then( function success( response ) {
                self.metadata.columnList.qnaire_id.enumList = [];
                for( var i = 0; i < response.data.length; i++ ) {
                  self.metadata.columnList.qnaire_id.enumList.push( {
                    value: response.data[i].id,
                    name: response.data[i].name
                  } );
                }
              } );
            } ).then( function() {
              self.metadata.loadingCount--;
            } );
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
