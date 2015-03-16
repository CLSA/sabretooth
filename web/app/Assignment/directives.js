define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnAssignmentAdd', function () {
    return {
      templateUrl: 'app/Assignment/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnAssignmentView', function () {
    return {
      templateUrl: 'app/Assignment/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
