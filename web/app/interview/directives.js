define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewAdd', function () {
    return {
      templateUrl: 'app/interview/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewView', function () {
    return {
      templateUrl: 'app/interview/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
