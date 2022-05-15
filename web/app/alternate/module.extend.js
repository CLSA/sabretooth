cenozoApp.extendModule({
  name: "alternate",
  create: (module) => {
    // extend the list factory
    cenozo.providers.decorator("CnAlternateModelFactory", [
      "$delegate",
      "CnSession",
      function ($delegate, CnSession) {
        // disable list for operators
        $delegate.root.module.extraOperationList.view.forEach((op) => {
          if ("Alternate List" == op.title)
            op.isIncluded = function () {
              return "operator" != CnSession.role.name;
            };
        });
        return $delegate;
      },
    ]);
  },
});
