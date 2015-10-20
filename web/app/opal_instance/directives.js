define( [], function() { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnOpalInstanceAdd', function () {
    return {
      templateUrl: 'app/opal_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnOpalInstanceView', function () {
    return {
      templateUrl: 'app/opal_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
