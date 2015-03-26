define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewAdd', function () {
    return {
      templateUrl: 'app/Interview/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnInterviewView', function () {
    return {
      templateUrl: 'app/Interview/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
