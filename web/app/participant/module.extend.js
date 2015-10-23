// extend the framework's module
define( [], function() {
  var module = cenozoApp.moduleList['participant'];

  module.inputList.queue_separator = {
    title: 'Queue Details',
    type: 'separator'
  };

  module.inputList.title = {
    title: 'Current Questionnaire',
    column: 'qnaire.title',
    type: 'string',
    constant: true
  };

  module.inputList.start_date = {
    title: 'Questionnaire Start',
    column: 'qnaire.start_date',
    type: 'date',
    constant: true
  };

  module.inputList.queue = {
    title: 'Current Queue',
    column: 'queue.name',
    type: 'string',
    constant: true
  };

  module.inputList.override_quota = {
    title: 'Override Quota',
    type: 'boolean'
  };

} );
