define( cenozo.getServicesIncludeList( 'interview' ), function( module ) {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewListFactory', [
    'CnBaseListFactory',
    function( CnBaseListFactory ) {
      var object = function( parentModel ) { CnBaseListFactory.construct( this, parentModel ); };
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewViewFactory',
    cenozo.getListModelInjectionList( 'interview' ).concat( function() {
      var args = arguments;
      var CnBaseViewFactory = args[0];
      var object = function( parentModel ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, args );

        // override onPatch
        this.onPatch = function( data ) {
          return this.patchRecord( data ).then( function() {
            // if the end datetime has changed then reload then update the appointment list actions
            if( angular.isDefined( data.end_datetime ) ) {
              var completed = null !== self.record.end_datetime;
              self.appointmentModel.enableAdd( !completed );
              self.appointmentModel.enableDelete( !completed );
              self.appointmentModel.enableEdit( !completed );
              self.appointmentModel.enableView( !completed );
            }
          } );
        };

        // override onView
        this.onView = function() {
          return this.viewRecord().then( function() {
            // if the end datetime has changed then reload then update the appointment list actions
            var completed = null !== self.record.end_datetime;
            self.appointmentModel.enableAdd( !completed );
            self.appointmentModel.enableDelete( !completed );
            self.appointmentModel.enableEdit( !completed );
            self.appointmentModel.enableView( !completed );
          } );
        };
      }
      return { instance: function( parentModel ) { return new object( parentModel ); } };
    } )
  );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnHttpFactory',
    function( CnBaseModelFactory, CnInterviewListFactory, CnInterviewViewFactory,
              CnHttpFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this );

        // extend getBreadcrumbTitle
        this.getBreadcrumbTitle = function() {
          var qnaire = self.metadata.columnList.qnaire_id.enumList.findByProperty(
            'value', this.viewModel.record.qnaire_id );
          return qnaire ? qnaire.name : 'unknown';
        };

        // extend getMetadata
        this.getMetadata = function() {
          this.metadata.loadingCount++;
          return this.loadMetadata().then( function() {
            return CnHttpFactory.instance( {
              path: 'interview_method',
              data: {
                select: { column: [ 'id', 'name' ] },
                modifier: { order: 'name' }
              }
            } ).query().then( function success( response ) {
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
                  modifier: { order: 'rank' }
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
              return CnHttpFactory.instance( {
                path: 'site',
                data: {
                  select: { column: [ 'id', 'name' ] },
                  modifier: { order: 'name' }
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
