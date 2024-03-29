cenozoApp.extendModule({
  name: "participant",
  create: (module) => {
    module.addInputGroup("Queue Details", {
      title: {
        title: "Current Questionnaire",
        column: "qnaire.title",
        type: "string",
        isConstant: true,
      },
      start_date: {
        title: "Delayed Until",
        column: "qnaire.start_date",
        type: "date",
        isConstant: true,
        help:
          "If not empty then the participant will not be permitted to begin this questionnaire until the " +
          "date shown is reached.",
      },
      queue: {
        title: "Current Queue",
        column: "queue.title",
        type: "string",
        isConstant: true,
      },
      override_stratum: {
        title: "Override Stratum",
        type: "boolean",
        isConstant: function ($state, model) {
          return !model.isRole("administrator");
        },
      },
    });

    module.addExtraOperation("view", {
      title: "Update Queue",
      isIncluded: function ($state, model) {
        return !model.isOperator;
      },
      isDisabled: function ($state, model) {
        return model.viewModel.isRepopulating;
      },
      operation: async function ($state, model) {
        await model.viewModel.onViewPromise;
        await model.viewModel.repopulate();
      },
    });

    angular.extend(module.historyCategoryList, {
      // appointments are added in the assignment's promise function below
      Appointment: { active: true },

      Assignment: {
        active: true,
        promise: async function (historyList, $state, CnHttpFactory) {
          var response = await CnHttpFactory.instance({
            path: "participant/" + $state.params.identifier + "/interview",
            data: {
              modifier: { order: { "interview.start_datetime": true } },
              select: { column: ["id"] },
            },
          }).query();

          await Promise.all(
            response.data.map(async (item) => {
              // appointments
              var subResponse = await CnHttpFactory.instance({
                path: "interview/" + item.id + "/appointment",
                data: {
                  modifier: {
                    join: [
                      {
                        table: "vacancy",
                        onleft: "appointment.start_vacancy_id",
                        onright: "vacancy.id",
                      },
                    ],
                    order: { "interview.start_datetime": true },
                  },
                  select: {
                    column: [
                      "outcome",
                      "assignment_id",
                      "user_id",
                      {
                        table: "vacancy",
                        column: "datetime",
                      },
                      {
                        table: "user",
                        column: "first_name",
                        alias: "user_first",
                      },
                      {
                        table: "user",
                        column: "last_name",
                        alias: "user_last",
                      },
                    ],
                  },
                },
              }).query();

              subResponse.data.forEach((item) => {
                var description = "An appointment scheduled for this time has ";
                if ("cancelled" == item.outcome) {
                  description += "been cancelled.";
                } else {
                  description += item.assignment_id
                    ? "been met.\nDuring the call the participant was " +
                      item.outcome +
                      ".\n"
                    : "not yet been met.";
                }
                historyList.push({
                  datetime: item.datetime,
                  category: "Appointment",
                  title:
                    "scheduled for " +
                    (null == item.user_id
                      ? "any operator"
                      : item.user_first + " " + item.user_last),
                  description: description,
                });
              });

              // assignments
              var subResponse = await CnHttpFactory.instance({
                path: "interview/" + item.id + "/assignment",
                data: {
                  modifier: { order: { "assignment.start_datetime": true } },
                  select: {
                    column: [
                      "start_datetime",
                      "end_datetime",
                      {
                        table: "user",
                        column: "first_name",
                        alias: "user_first",
                      },
                      {
                        table: "user",
                        column: "last_name",
                        alias: "user_last",
                      },
                      {
                        table: "site",
                        column: "name",
                        alias: "site",
                      },
                      {
                        table: "script",
                        column: "name",
                        alias: "script",
                      },
                      {
                        table: "queue",
                        column: "name",
                        alias: "queue",
                      },
                    ],
                  },
                },
              }).query();

              subResponse.data.forEach((item) => {
                if (null != item.start_datetime) {
                  historyList.push({
                    datetime: item.start_datetime,
                    category: "Assignment",
                    title:
                      "started by " + item.user_first + " " + item.user_last,
                    description:
                      'Started an assignment for the "' +
                      item.script +
                      '" questionnaire.\n' +
                      "Assigned from the " +
                      item.site +
                      " site " +
                      'from the "' +
                      item.queue +
                      '" queue.',
                  });
                }
                if (null != item.end_datetime) {
                  historyList.push({
                    datetime: item.end_datetime,
                    category: "Assignment",
                    title:
                      "completed by " + item.user_first + " " + item.user_last,
                    description:
                      'Completed an assignment for the "' +
                      item.script +
                      '" questionnaire.\n' +
                      "Assigned from the " +
                      item.site +
                      " site " +
                      'from the "' +
                      item.queue +
                      '" queue.',
                  });
                }
              });
            })
          );
        },
      },
    });

    // extend the view factory
    cenozo.providers.decorator("CnParticipantViewFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          angular.extend(object, {
            // force the default tab to be "interview"
            defaultTab: "interview",
            isRepopulating: false,
            repopulate: async function () {
              try {
                object.isRepopulating = true;
                await CnHttpFactory.instance({
                  path:
                    object.parentModel.getServiceResourcePath() +
                    "?repopulate=1",
                }).patch();
                await object.onView();
              } finally {
                object.isRepopulating = false;
              }
            },
          });

          return object;
        };
        return $delegate;
      },
    ]);

    // extend the list factory
    cenozo.providers.decorator("CnParticipantModelFactory", [
      "$delegate",
      "CnSession",
      function ($delegate, CnSession) {
        // disable list for operators
        $delegate.root.getListEnabled = function () {
          return "operator" == CnSession.role.name
            ? false
            : this.$$getListEnabled();
        };
        $delegate.root.isOperator = "operator" == CnSession.role.name;
        return $delegate;
      },
    ]);
  },
});
