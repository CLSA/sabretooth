// extend the framework's module
define( [ cenozoApp.module( 'alternate' ).getFileUrl( 'module.js' ) ], function() {
  'use strict';

  // extend the list factory
  cenozo.providers.decorator( 'CnAlternateModelFactory', [
    '$delegate', 'CnSession',
    function( $delegate, CnSession ) {
      // disable list for operators
      $delegate.root.module.extraOperationList.view.forEach( function( op ) {
        if( 'Alternate List' == op.title )
          op.isIncluded = function() { return 'operator' != CnSession.role.name; }
      } );
      return $delegate;
    }
  ] );

} );
