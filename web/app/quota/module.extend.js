// extend the framework's module
define( [ cenozoApp.module( 'quota' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  // extend the view factory
  cenozo.providers.decorator( 'CnQuotaViewFactory', function( $delegate ) {
    var instance = $delegate.instance;
    $delegate.instance = function( parentModel, root ) {
      var object = instance( parentModel, root );
      object.deferred.promise.then( function() {
        if( angular.isDefined( object.qnaireModel ) )
          object.qnaireModel.listModel.heading = 'Disabled Questionnaire List';
      } );
      return object;
    };
    return $delegate;
  } );
  
} );
