define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQnaireAdd', function () {
    return {
      templateUrl: 'app/Qnaire/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQnaireView', function () {
    return {
      templateUrl: 'app/Qnaire/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
