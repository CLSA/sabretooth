// extend the framework's module
define( [ cenozoApp.module( 'quota' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'quota' );
  
  // overwrite the view factory
  cenozo.providers.factory( 'CnQuotaViewFactory', [
    'CnBaseViewFactory',
    function( CnBaseViewFactory ) { 
      var object = function( parentModel, root ) {
        var self = this;
        CnBaseViewFactory.construct( this, parentModel, root );

        this.deferred.promise.then( function() {
          if( angular.isDefined( self.qnaireModel ) )
            self.qnaireModel.listModel.heading = 'Disabled Questionnaire List';
        } );
      }
      return { instance: function( parentModel, root ) { return new object( parentModel, root ); } };
    }
  ] );
  
} );
