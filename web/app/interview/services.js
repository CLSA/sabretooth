define( [
  'app/interview/module.js',
  'app/assignment/bootstrap.js'
], function( module ) {
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
    'CnBaseViewFactory', 'CnAssignmentModelFactory',
    function( CnBaseViewFactory, CnAssignmentModelFactory ) {
      var object = function( parentModel ) {
        CnBaseViewFactory.construct( this, parentModel );

        ////////////////////////////////////
        // factory customizations start here
        var self = this;
        this.assignmentModel = CnAssignmentModelFactory.instance();
        this.assignmentModel.enableAdd( this.parentModel.editEnabled );
        this.assignmentModel.enableDelete( this.parentModel.editEnabled );
        this.assignmentModel.enableView( this.parentModel.viewEnabled );

        this.onView = function view() {
          return this.viewRecord().then( function() {
            self.assignmentModel.listModel.onList( true );
          } );
        };
        // factory customizations end here
        ////////////////////////////////////
      };

      return { instance: function( parentModel ) { return new object( parentModel ); } };
    }
  ] );

  /* ######################################################################################################## */
  cenozo.providers.factory( 'CnInterviewModelFactory', [
    'CnBaseModelFactory', 'CnInterviewAddFactory', 'CnInterviewListFactory', 'CnInterviewViewFactory',
    'CnHttpFactory',
    function( CnBaseModelFactory, CnInterviewAddFactory, CnInterviewListFactory, CnInterviewViewFactory,
              CnHttpFactory ) {
      var object = function() {
        var self = this;
        CnBaseModelFactory.construct( this, module );
        this.addModel = CnInterviewListFactory.instance( this );
        this.listModel = CnInterviewListFactory.instance( this );
        this.viewModel = CnInterviewViewFactory.instance( this );

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
                  modifier: { order: { rank: false } }
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
