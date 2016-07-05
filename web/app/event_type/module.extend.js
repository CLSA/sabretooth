// extend the framework's module
define( [ cenozoApp.module( 'event_type' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'event_type' );
  
  // overwrite the view factory
  cenozo.providers.factory( 'CnEventTypeViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) { 
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.qnaireModel ) )
            self.qnaireModel.listModel.heading = 'Required To Begin Questionnaires List';
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );
  
} );
