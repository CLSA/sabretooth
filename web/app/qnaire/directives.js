define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQnaireAdd', function () {
    return {
      templateUrl: 'app/qnaire/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQnaireView', function () {
    return {
      templateUrl: 'app/qnaire/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
