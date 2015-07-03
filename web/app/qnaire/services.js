define( cenozo.getServicesIncludeList( 'qnaire' ), function( module ) { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireAddFactory', [
    'CnBaseAddFactory',
    function( CnBaseAddFactory ) {
      var object = function( parentModel ) { CnBaseAddFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } 
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireViewFactory',
    cenozo.getListModelInjectionList( 'qnaire' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) { CnBaseViewFactory.construct( this, parentModel, args ); }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnQnaireModelFactory', [
    'CnBaseModelFactory', 'CnQnaireAddFactory', 'CnQnaireListFactory', 'CnQnaireViewFactory',
    'CnHttpFactory',
    function( CnBaseModelFactory, CnQnaireAddFactory, CnQnaireListFactory, CnQnaireViewFactory,
              CnHttpFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnQnaireAddFactory.instance( this );
        this.listModel = CnQnaireListFactory.instance( this );
        this.viewModel = CnQnaireViewFactory.instance( this );

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'interview_method',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: { name: false } }
              }
            } ).query().then( function( response ) {
              self.metadata.columnList.interview_method_id.enumList = [];
              for( var i = 0; i < response.data.length; i++ ) {
                self.metadata.columnList.interview_method_id.enumList.push( {
                  value: response.data[i].id,
                  name: response.data[i].name
                } );
              }
            } ).then( function() {
              return CnHttpFactory.instance( {
                path: 'qnaire',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: { name: false } }
                }
              } ).query().then( function( response ) {
                self.metadata.columnList.prev_qnaire_id.enumList = [];
                for( var i = 0; i < response.data.length; i++ ) {
                  self.metadata.columnList.prev_qnaire_id.enumList.push( {
                    value: response.data[i].id,
                    name: response.data[i].name
                  } );
                }
              } );
            } ).then( function() {
              return CnHttpFactory.instance( {
                path: 'survey',
                data: {
                  select: { column: [ 'sid', 'title' ] },
                  modifier: { order: { title: false } }
                }
              } ).query().then( function( response ) {
                self.metadata.columnList.withdraw_sid.enumList = [];
                for( var i = 0; i < response.data.length; i++ ) {
                  self.metadata.columnList.withdraw_sid.enumList.push( {
                    value: response.data[i].sid,
                    name: response.data[i].title
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
