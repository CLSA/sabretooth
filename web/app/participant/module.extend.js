// extend the framework's module
var module = cenozoApp.module( 'participant' );
define( [ module.url + 'module.js' ], function() {

  module.addInputGroup( 'Queue Details', {
    title: {
      title: 'Current Questionnaire',
      column: 'qnaire.title',
      type: 'string',
      constant: true
    },
    start_date: {
      title: 'Questionnaire Start',
      column: 'qnaire.start_date',
      type: 'date',
      constant: true
    },
    queue: {
      title: 'Current Queue',
      column: 'queue.name',
      type: 'string',
      constant: true
    },
    override_quota: {
      title: 'Override Quota',
      type: 'boolean'
    }
  } );

} );
