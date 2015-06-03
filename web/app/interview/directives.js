define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewAdd', function () {
    return {
      templateUrl: 'app/interview/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cenozo.providers.directive( 'cnInterviewView', function () {
    return {
      templateUrl: 'app/interview/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
