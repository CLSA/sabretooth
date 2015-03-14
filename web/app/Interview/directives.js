define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewAdd', function () {
    return {
      interviewUrl: 'app/interview/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewView', function () {
    return {
      interviewUrl: 'app/interview/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
