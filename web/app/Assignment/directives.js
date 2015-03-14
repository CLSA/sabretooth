define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnAssignmentAdd', function () {
    return {
      assignmentUrl: 'app/assignment/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnAssignmentView', function () {
    return {
      assignmentUrl: 'app/assignment/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
