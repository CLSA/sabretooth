define( [], function() { 
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentAdd', function () {
    return {
      templateUrl: 'app/appointment/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnAppointmentView', function () {
    return {
      templateUrl: 'app/appointment/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
