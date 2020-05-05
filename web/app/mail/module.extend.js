// extend the framework's module
define( [ cenozoApp.module( 'mail' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  var module = cenozoApp.module( 'mail' );

  // extend the model factory
  cenozo.providers.decorator( 'CnMailAddFactory', [
    '$delegate', 'CnSession',
    function( $delegate, CnSession ) {
      var instance = $delegate.instance;

      $delegate.instance = function( parentModel ) {
        var object = instance( parentModel );

        // extend onNew
        var onNew = object.onNew;
        object.onNew = function( record ) {
          return onNew( record ).then( function() {
            if( CnSession.setting.mailName ) record.from_name = CnSession.setting.mailName;
            if( CnSession.setting.mailAddress ) record.from_address = CnSession.setting.mailAddress;
          } );
        };

        return object;
      };

      return $delegate;
    }
  ] );

} );
