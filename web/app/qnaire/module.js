cenozoApp.defineModule({
  name: "qnaire",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: { column: "rank" },
      name: {
        singular: "questionnaire",
        plural: "questionnaires",
        possessive: "questionnaire's",
      },
      columnList: {
        name: {
          column: "script.name",
          title: "Name",
        },
        rank: {
          title: "Rank",
          type: "rank",
        },
        allow_missing_consent: {
          title: "Missing Consent",
          type: "boolean",
        },
        web_version: {
          title: "Web Version",
          type: "boolean",
        },
        delay_offset: {
          title: "Delay Offset",
          type: "number",
        },
        delay_unit: {
          title: "Delay Unit",
          type: "string",
        },
      },
      defaultOrder: {
        column: "rank",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      rank: {
        column: "qnaire.rank",
        title: "Rank",
        type: "rank",
      },
      script_id: {
        title: "Script",
        type: "enum",
        isConstant: "view",
        help: "Only scripts which are marked as non-repeatable may be used as a questionnaire.",
      },
      allow_missing_consent: {
        title: "Allow Missing Consent",
        type: "boolean",
        help: "This field determines whether or not a participant should be allowed to proceed with the questionnaire when they are missing the extra consent record specified by the study.",
      },
      web_version: {
        title: "Web Version",
        type: "boolean",
        isConstant: function ($state, model) {
          // don't allow non-Pine scripts to have a web version
          return (
            "add" != model.getActionFromState() &&
            null == model.viewModel.record.pine_qnaire_id
          );
        },
        help: "Defines whether this questionnaire has a web-version.",
      },
      delay_offset: {
        title: "Delay Offset",
        type: "string",
        format: "integer",
        minValue: 0,
      },
      delay_unit: {
        title: "Delay Unit",
        type: "enum",
      },
      pine_qnaire_id: {
        column: "script.pine_qnaire_id",
        type: "hidden",
      },
    });

    module.addExtraOperation("view", {
      title: "Mass Interview Method",
      operation: async function ($state, model) {
        await $state.go("qnaire.mass_method", {
          identifier: model.viewModel.record.getIdentifier(),
        });
      },
      isIncluded: function ($state, model) {
        return (
          model.getEditEnabled() &&
          null != model.viewModel.record.pine_qnaire_id
        );
      },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnQnaireMassMethod", [
      "CnQnaireMassMethodFactory",
      "CnSession",
      "$state",
      function (CnQnaireMassMethodFactory, CnSession, $state) {
        return {
          templateUrl: module.getFileUrl("mass_method.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: async function ($scope) {
            if (angular.isUndefined($scope.model))
              $scope.model = CnQnaireMassMethodFactory.instance();
            await $scope.model.onLoad();
            CnSession.setBreadcrumbTrail([
              {
                title: "Questionnaires",
                go: async function () {
                  await $state.go("qnaire.list");
                },
              },
              {
                title: $scope.model.qnaireName,
                go: async function () {
                  await $state.go("qnaire.view", {
                    identifier: $scope.model.qnaireId,
                  });
                },
              },
              {
                title: "Mass Interview Method",
              },
            ]);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQnaireMassMethodFactory", [
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      "CnParticipantSelectionFactory",
      "$state",
      function (
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory,
        CnParticipantSelectionFactory,
        $state
      ) {
        var object = function () {
          angular.extend(this, {
            method: "phone",
            working: false,
            qnaireId: $state.params.identifier,
            qnaireName: null,
            participantSelection: CnParticipantSelectionFactory.instance({
              path: ["qnaire", $state.params.identifier, "participant"].join(
                "/"
              ),
              data: { mode: "confirm", method: "phone" },
            }),
            onLoad: async function () {
              // reset data
              var response = await CnHttpFactory.instance({
                path: "qnaire/" + this.qnaireId,
                data: { select: { column: "name" } },
              }).get();

              this.qnaireName = response.data.name;
              this.participantSelection.reset();
            },

            inputsChanged: function () {
              this.participantSelection.data.method = this.method;
              this.participantSelection.reset();
            },

            proceed: async function () {
              if (
                !this.participantSelection.confirmInProgress &&
                0 < this.participantSelection.confirmedCount
              ) {
                try {
                  this.working = true;
                  var response = await CnHttpFactory.instance({
                    path: ["qnaire", this.qnaireId, "participant"].join("/"),
                    data: {
                      mode: "update",
                      identifier_id: this.participantSelection.identifierId,
                      identifier_list:
                        this.participantSelection.getIdentifierList(),
                      method: this.method,
                    },
                    onError: async function (error) {
                      await CnModalMessageFactory.httpError(error);
                      await this.onLoad();
                    },
                  }).post();

                  await CnModalMessageFactory.instance({
                    title: "Interview Methods Updated",
                    message:
                      "You have successfully changed " +
                      this.participantSelection.confirmedCount +
                      ' "' +
                      this.qnaireName +
                      '" questionnaires ' +
                      "to using the " +
                      this.method +
                      " interviewing method.",
                  }).show();
                  this.onLoad();
                } finally {
                  this.working = false;
                }
              }
            },
          });
        };
        return {
          instance: function () {
            return new object();
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQnaireViewFactory", [
      "CnBaseViewFactory",
      function (CnBaseViewFactory) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root, "collection");

          async function init(object) {
            await object.deferred.promise;
            if (angular.isDefined(object.collectionModel))
              object.collectionModel.listModel.heading =
                "Disabled Collection List";
            if (angular.isDefined(object.holdTypeModel))
              object.holdTypeModel.listModel.heading =
                "Overridden Hold Type List";
            if (angular.isDefined(object.siteModel))
              object.siteModel.listModel.heading = "Disabled Site List";
            if (angular.isDefined(object.stratumModel))
              object.stratumModel.listModel.heading = "Disabled Stratum List";
          }

          init(this);
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnQnaireModelFactory", [
      "CnBaseModelFactory",
      "CnQnaireAddFactory",
      "CnQnaireListFactory",
      "CnQnaireViewFactory",
      "CnHttpFactory",
      function (
        CnBaseModelFactory,
        CnQnaireAddFactory,
        CnQnaireListFactory,
        CnQnaireViewFactory,
        CnHttpFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);
          this.addModel = CnQnaireAddFactory.instance(this);
          this.listModel = CnQnaireListFactory.instance(this);
          this.viewModel = CnQnaireViewFactory.instance(this, root);

          // extend getMetadata
          this.getMetadata = async function () {
            await this.$$getMetadata();

            var response = await CnHttpFactory.instance({
              path: "application/0/script",
              data: {
                select: { column: ["id", "name"] },
                modifier: {
                  where: [{ column: "repeated", operator: "=", value: false }],
                  order: "name",
                  limit: 1000,
                },
              },
            }).query();

            this.metadata.columnList.script_id.enumList = [];
            response.data.forEach((item) => {
              this.metadata.columnList.script_id.enumList.push({
                value: item.id,
                name: item.name,
              });
            });
          };
        };

        return {
          root: new object(true),
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);
  },
});
