define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCedarInstanceAdd', function () {
    return {
      templateUrl: 'app/cedar_instance/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnCedarInstanceView', function () {
    return {
      templateUrl: 'app/cedar_instance/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
