define( [], function() {

  'use strict';

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQuestionnaireAdd', function () {
    return {
      qnaireUrl: 'app/qnaire/add.tpl.html',
      restrict: 'E'
    };
  } );

  /* ######################################################################################################## */
  cnCachedProviders.directive( 'cnQuestionnaireView', function () {
    return {
      qnaireUrl: 'app/qnaire/view.tpl.html',
      restrict: 'E'
    };
  } );

} );
