cenozoApp.extendModule({
  name: "interview",
  create: (module) => {
    cenozo.insertPropertyAfter(module.columnList, "uid", "qnaire", {
      column: "script.name",
      title: "Questionnaire",
    });

    cenozo.insertPropertyAfter(module.columnList, "qnaire", "method", {
      column: "interview.method",
      title: "Method",
    });

    cenozo.insertPropertyAfter(module.columnList, "site", "page_progress", {
      title: "Page Progress",
    });

    // add future_appointment as a hidden input (to be used below)
    module.addInput("", "future_appointment", { type: "hidden" });
    module.addInput("", "last_participation_consent", { type: "hidden" });

    // add whether the script is pine based or not (needed to enable/disable qnaire method)
    module.addInput("", "pine_qnaire_id", {
      column: "script.pine_qnaire_id",
      type: "hidden",
    });

    module.addInput(
      "",
      "qnaire_id",
      {
        title: "Questionnaire",
        type: "enum",
        isConstant: true,
      },
      "participant"
    );

    module.addInput(
      "",
      "method",
      {
        title: "Interviewing Method",
        type: "enum",
        // don't allow the interviewing method to be changed if it is done or not doing a pine interview
        isConstant: function ($state, model) {
          return (
            null != model.viewModel.record.end_datetime ||
            null == model.viewModel.record.pine_qnaire_id
          );
        },
      },
      "qnaire_id"
    );

    module.addInput(
      "",
      "page_progress",
      {
        title: "Page Progress",
        type: "string",
        isConstant: true,
      },
      "site_id"
    );

    module.addExtraOperation("view", {
      title: "Force Remove",
      operation: function ($state, model) {
        model.viewModel.forceRemove();
      },
      isIncluded: function ($state, model) {
        return model.viewModel.forceOperationsAllowed;
      },
      classes: "btn-danger",
      help:
        "Force removes the interview. " +
        "This will delete all appointments, calls, and assignments along with the interview. " +
        "Questionnaires associated with the interview will not be changed and must be edited/deleted directly.",
    });

    module.addExtraOperation("view", {
      title: "Force Complete",
      operation: function ($state, model) {
        model.viewModel.forceComplete();
      },
      isDisabled: function ($state, model) {
        return null !== model.viewModel.record.end_datetime;
      },
      isIncluded: function ($state, model) {
        return model.viewModel.forceOperationsAllowed;
      },
      classes: "btn-danger",
      help:
        "Force completes the interview. " +
        "This will end the interview's questionnaire leaving any remaining questions unanswered. " +
        "You should only force-close an interview when you are sure that as many questions in the " +
        "questionnaire has been answered as possible and there is no reason to re-assign the participant.",
    });

    // extend the list factory
    cenozo.providers.decorator("CnInterviewListFactory", [
      "$delegate",
      "CnHttpFactory",
      function ($delegate, CnHttpFactory) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel) {
          var object = instance(parentModel);

          // enable the add button if:
          //   1) the interview list's parent is a participant model
          //   2) all interviews are complete for this participant
          //   3) another qnaire is available for this participant
          object.afterList(async function () {
            object.parentModel.getAddEnabled = function () {
              return false;
            };

            var path = object.parentModel.getServiceCollectionPath();
            if (
              "participant" == object.parentModel.getSubjectFromState() &&
              null !== path.match(/participant\/[^\/]+\/interview/)
            ) {
              var queueRank = null;
              var qnaireRank = null;
              var lastInterview = null;

              // get the participant's last interview
              var response = await CnHttpFactory.instance({
                path: path,
                data: {
                  modifier: { order: { "qnaire.rank": true }, limit: 1 },
                  select: {
                    column: [
                      { table: "qnaire", column: "rank" },
                      "end_datetime",
                    ],
                  },
                },
                onError: function (error) {}, // ignore errors
              }).query();

              if (0 < response.data.length) lastInterview = response.data[0];

              // get the participant's current queue rank
              var response = await CnHttpFactory.instance({
                path: path.replace("/interview", ""),
                data: {
                  select: {
                    column: [
                      { table: "queue", column: "rank", alias: "queueRank" },
                      { table: "qnaire", column: "rank", alias: "qnaireRank" },
                    ],
                  },
                },
                onError: function (error) {}, // ignore errors
              }).query();

              queueRank = response.data.queueRank;
              qnaireRank = response.data.qnaireRank;

              object.parentModel.getAddEnabled = function () {
                return (
                  object.parentModel.$$getAddEnabled() &&
                  null != queueRank &&
                  null != qnaireRank &&
                  (null == lastInterview ||
                    (null != lastInterview.end_datetime &&
                      lastInterview.rank != qnaireRank))
                );
              };
            }
          });

          return object;
        };
        return $delegate;
      },
    ]);

    // extend the view factory
    cenozo.providers.decorator("CnInterviewViewFactory", [
      "$delegate",
      "CnSession",
      "CnHttpFactory",
      "CnModalConfirmFactory",
      "CnModalMessageFactory",
      function (
        $delegate,
        CnSession,
        CnHttpFactory,
        CnModalConfirmFactory,
        CnModalMessageFactory
      ) {
        var instance = $delegate.instance;
        $delegate.instance = function (parentModel, root) {
          var object = instance(parentModel, root);

          angular.extend(object, {
            // force the default tab to be "appointment"
            defaultTab: "appointment",

            forceOperationsAllowed: 2 < CnSession.role.tier,

            forceRemove: async function () {
              var response = await CnModalConfirmFactory.instance({
                title: "Force Remove Interview?",
                message:
                  "Are you sure you wish to force-remove the interview?\n\n" +
                  "Note that all appointments, phone calls, and assignments associated with the " +
                  "interview will also be deleted.  Questionnaires will not be changed, and start/finish " +
                  "events will not be deleted.  This operation cannot be undone.",
                onError: function (response) {
                  if (409 == response.status) {
                    // 409 means there is an open assignment (or some other problem which we can report)
                    CnModalMessageFactory.instance({
                      title: "Unable to delete interview",
                      message: response.data,
                      error: true,
                    }).show();
                  } else {
                    CnModalMessageFactory.httpError(response);
                  }
                },
              }).show();

              if (response) {
                try {
                  await CnHttpFactory.instance({
                    path:
                      "interview/" +
                      object.record.id +
                      "?operation=force_delete",
                    data: {},
                  }).patch();

                  await object.transitionOnViewParent("participant");
                } catch (error) {
                  // handled by onError above
                }
              }
            },

            forceComplete: async function () {
              var response = await CnModalConfirmFactory.instance({
                title: "Force Complete Interview?",
                message:
                  "Are you sure you wish to force-complete the interview?\n\n" +
                  "Note that the interview's questionnaire will be closed and unanswered questions will " +
                  "no longer be accessible.  This operation cannot be undone.",
              }).show();

              if (response) {
                try {
                  await CnHttpFactory.instance({
                    path:
                      "interview/" +
                      object.record.id +
                      "?operation=force_complete",
                    data: {},
                    onError: function (response) {
                      if (409 == response.status) {
                        // 409 means there is an open assignment (or some other problem which we can report)
                        CnModalMessageFactory.instance({
                          title: "Unable to close interview",
                          message: response.data,
                          error: true,
                        }).show();
                      } else {
                        CnModalMessageFactory.httpError(response);
                      }
                    },
                  }).patch();

                  await object.onView();
                } catch (error) {
                  // handled by onError above
                }
              }
            },

            // override onView
            onView: async function (force) {
              await object.$$onView(force);
              if (angular.isDefined(object.appointmentModel))
                updateEnableFunctions();
            },
          });

          function getAppointmentEnabled(type) {
            var completed = null !== object.record.end_datetime;
            var participating =
              false !== object.record.last_participation_consent;
            var future = object.record.future_appointment;
            return "add" == type
              ? !completed && participating && !future
              : future;
          }

          function updateEnableFunctions() {
            object.appointmentModel.getAddEnabled = function () {
              return (
                angular.isDefined(object.appointmentModel.module.actions.add) &&
                getAppointmentEnabled("add")
              );
            };
            object.appointmentModel.getDeleteEnabled = function () {
              return (
                angular.isDefined(
                  object.appointmentModel.module.actions.delete
                ) &&
                getAppointmentEnabled("delete") &&
                "vacancy" != parentModel.getSubjectFromState()
              );
            };
          }

          async function init() {
            // override appointment list's onDelete
            await object.deferred.promise;

            if (angular.isDefined(object.appointmentModel)) {
              object.appointmentModel.listModel.onDelete = async function (
                record
              ) {
                await object.appointmentModel.listModel.$$onDelete(record);
                await object.onView();
              };
            }
          }

          init();

          return object;
        };
        return $delegate;
      },
    ]);

    // extend the model factory
    cenozo.providers.decorator("CnInterviewModelFactory", [
      "$delegate",
      "CnHttpFactory",
      "CnSession",
      function ($delegate, CnHttpFactory, CnSession) {
        var instance = $delegate.instance;

        // (metadata's promise will have already returned so we don't have to wait for it)
        function extendObject(object) {
          // keep the original getMetadata() function and call it below
          object.oldGetMetadata = object.getMetadata;

          angular.extend(object, {
            getBreadcrumbTitle: function () {
              var qnaire =
                object.metadata.columnList.qnaire_id.enumList.findByProperty(
                  "value",
                  object.viewModel.record.qnaire_id
                );
              return qnaire ? qnaire.name : "unknown";
            },

            getEditEnabled: function () {
              return (
                object.$$getEditEnabled() &&
                ["administrator", "helpline"].includes(CnSession.role.name)
              );
            },

            // extend getMetadata
            getMetadata: async function () {
              await object.oldGetMetadata();

              var response = await CnHttpFactory.instance({
                path: "qnaire",
                data: {
                  select: {
                    column: ["id", { table: "script", column: "name" }],
                  },
                  modifier: { order: "rank" },
                },
              }).query();

              object.metadata.columnList.qnaire_id.enumList =
                response.data.reduce((list, item) => {
                  list.push({ value: item.id, name: item.name });
                  return list;
                }, []);
            },
          });
        }

        extendObject($delegate.root);

        $delegate.instance = function () {
          var object = instance();
          extendObject(object);
          return object;
        };

        return $delegate;
      },
    ]);
  },
});
