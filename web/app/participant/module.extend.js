// extend the framework's module
define(
  [ cenozo.baseUrl + '/app/participant/module.js' ],
  function() {
    module.inputList.override_quota = {
      title: 'Override Quota',
      type: 'boolean'
    };
  }
);
