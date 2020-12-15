// extend the framework's module
define( [ cenozoApp.module( 'stratum' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  // extend the view factory
  cenozo.providers.decorator( 'CnStratumViewFactory', [
    '$delegate', 'CnSession',
    function( $delegate, CnSession ) {
      var instance = $delegate.instance;
      $delegate.instance = function( parentModel, root ) {
        var object = instance( parentModel, root );
        object.deferred.promise.then( function() {
          if( angular.isDefined( object.qnaireModel ) ) {
            object.qnaireModel.getChooseEnabled = function() {
              return object.qnaireModel.$$getChooseEnabled() && 3 <= CnSession.role.tier;
            };
            object.qnaireModel.listModel.heading = 'Disabled Questionnaire List';
          }
        } );
        return object;
      };
      return $delegate;
    }
  ] );

} );
