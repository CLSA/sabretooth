// extend the framework's module
define( [ cenozoApp.module( 'site' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'site' );
  
  // overwrite the view factory
  console.info( 'Overriding CnSiteViewFactory factory' );
  cenozo.providers.factory( 'CnSiteViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) {
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        // extend the onPatch function
        this.onPatch = function( data ) {
          return self.$$onPatch( data ).then( function() {
            // update the region
            if( angular.isDefined( data.postcode ) ) self.onView();
          } );
        };

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.qnaireModel ) )
            self.qnaireModel.listModel.heading = 'Disabled Questionnaire List';
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );
  
} );
