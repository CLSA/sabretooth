define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnPhaseAdd', function () {
    return {
      templateUrl: 'app/phase/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnPhaseView', function () {
    return {
      templateUrl: 'app/phase/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
