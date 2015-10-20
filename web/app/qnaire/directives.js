define( [], function() {
  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireAdd', function () {
    return {
      templateUrl: 'app/qnaire/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnQnaireView', function () {
    return {
      templateUrl: 'app/qnaire/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
