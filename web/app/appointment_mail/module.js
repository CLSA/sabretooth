cenozoApp.defineModule({
  name: "appointment_mail",
  dependencies: "trace",
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {
        parent: {
          subject: "site",
          column: "site.name",
        },
      },
      name: {
        singular: "appointment mail template",
        plural: "appointment mail templates",
        possessive: "appointment mail template's",
      },
      columnList: {
        site: {
          column: "site.name",
          title: "Site",
        },
        language: {
          column: "language.name",
          title: "Language",
        },
        delay: {
          title: "Delay",
        },
        subject: {
          title: "Subject",
        },
      },
      defaultOrder: {
        column: "delay",
        reverse: false,
      },
    });

    module.addInputGroup("", {
      site_id: {
        title: "Site",
        type: "enum",
        isExcluded: function ($state, model) {
          return model.hasAllSites() ? "add" : true;
        },
        isConstant: "view",
      },
      language_id: {
        title: "Language",
        type: "enum",
        isConstant: "view",
      },
      from_name: {
        title: "From Name",
        type: "string",
      },
      from_address: {
        title: "From Address",
        type: "string",
        format: "eappointment_mail",
        help: 'Must be in the format "account@domain.name".',
      },
      cc_address: {
        title: "Carbon Copy (CC)",
        type: "string",
        help: 'May be a comma-delimited list of eappointment_mail addresses in the format "account@domain.name".',
      },
      bcc_address: {
        title: "Blind Carbon Copy (BCC)",
        type: "string",
        help: 'May be a comma-delimited list of eappointment_mail addresses in the format "account@domain.name".',
      },
      delay_offset: {
        title: "Delay (days)",
        type: "string",
        format: "integer",
        isExcluded: function ($state, model) {
          // temporary until add scope is loaded
          return "immediately" == model.viewModel.record.delay_unit ? true : "add";
        },
      },
      delay_unit: {
        title: "Delay Type",
        type: "enum",
      },
      subject: {
        title: "Subject",
        type: "string",
      },
      body: {
        title: "Body",
        type: "text",
      },
    });

    module.addExtraOperation("view", {
      title: "Preview",
      operation: function ($state, model) {
        model.viewModel.preview();
      },
    });

    module.addExtraOperation("view", {
      title: "Validate",
      operation: function ($state, model) {
        model.viewModel.validate();
      },
    });

    /* ############################################################################################## */
    cenozo.providers.directive("cnAppointmentMailAdd", [
      "CnAppointmentMailModelFactory",
      function (CnAppointmentMailModelFactory) {
        return {
          templateUrl: module.getFileUrl("add.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            if (angular.isUndefined($scope.model))
              $scope.model = CnAppointmentMailModelFactory.root;

            // get the child cn-record-add's scope
            $scope.$on("cnRecordAdd ready", async function (event, data) {
              var cnRecordAddScope = data;
              var checkFunction = cnRecordAddScope.check;

              angular.extend(cnRecordAddScope, {
                // Only show the delay offset if the delay unit value is not "immediately"
                updateDelayOffsetInputVisibility: function() {
                  const group = this.model.module.inputGroupList.findByProperty( "title", "" );
                  group.inputList.delay_offset.isExcluded = function($state, model) {
                    return "view" == model.getActionFromState() ?
                      "immediately" == model.viewModel.record.delay_unit :
                      "immediately" == cnRecordAddScope.record.delay_unit;
                  };
                },

                check: function (property) {
                  // run the original check function first
                  checkFunction(property);

                  if ("delay_unit" == property) {
                    // Update whether the delay offset input is visible
                    this.updateDelayOffsetInputVisibility();
                  }
                },
              });

              // make sure to define whether or not to show the delay offset input
              cnRecordAddScope.updateDelayOffsetInputVisibility();
            });
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentMailAddFactory", [
      "CnBaseAddFactory",
      "CnHttpFactory",
      function (CnBaseAddFactory, CnHttpFactory) {
        var object = function (parentModel) {
          CnBaseAddFactory.construct(this, parentModel);

          this.onNew = async function (record) {
            await this.$$onNew(record);

            var parent = this.parentModel.getParentIdentifier();
            var response = await CnHttpFactory.instance({
              path: "application/0",
              data: { select: { column: ["mail_name", "mail_address"] } },
            }).get();

            record.from_name = response.data.mail_name;
            record.from_address = response.data.mail_address;
          };
        };
        return {
          instance: function (parentModel) {
            return new object(parentModel);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentMailViewFactory", [
      "CnBaseViewFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalMessageFactory",
      function (
        CnBaseViewFactory,
        CnSession,
        CnHttpFactory,
        CnModalMessageFactory
      ) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          angular.extend(this, {
            onPatch: async function (data) {
              await this.$$onPatch(data);

              // The server-side will ensure that the delay_offset is empty when immediately is selected,
              // and delay_offset is not empty (any integer) when days is selected, so reflect that here
              if( angular.isDefined(data.delay_offset) || angular.isDefined(data.delay_unit) ) {
                if( "immediately" == this.record.delay_unit && null != this.record.delay_offset ) {
                  this.record.delay_offset = null;
                } else if( "immediately" != this.record.delay_unit && null == this.record.delay_offset ) {
                  this.record.delay_offset = 1;
                }
              }
            },

            preview: async function () {
              var response = await CnHttpFactory.instance({
                path: "application/" + CnSession.application.id,
                data: { select: { column: ["mail_header", "mail_footer"] } },
              }).get();

              var body = this.record.body;
              if (null != response.data.mail_header)
                body = response.data.mail_header + "\n" + body;
              if (null != response.data.mail_footer)
                body = body + "\n" + response.data.mail_footer;
              await CnModalMessageFactory.instance({
                title: "Mail Preview",
                message: body,
                html: true,
              }).show();
            },

            validate: async function () {
              var response = await CnHttpFactory.instance({
                path: this.parentModel.getServiceResourcePath(),
                data: { select: { column: "validate" } },
              }).get();

              var result = JSON.parse(response.data.validate);

              var message = "The subject contains ";
              message +=
                null == result || angular.isUndefined(result.subject)
                  ? "no errors.\n"
                  : "the invalid variable $" + result.subject + "$.";

              message += "The body contains ";
              message +=
                null == result || angular.isUndefined(result.body)
                  ? "no errors.\n"
                  : "the invalid variable $" + result.body + "$.";

              await CnModalMessageFactory.instance({
                title: "Validation Result",
                message: message,
              }).show();
            },
          });
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAppointmentMailModelFactory", [
      "CnBaseModelFactory",
      "CnAppointmentMailListFactory",
      "CnAppointmentMailAddFactory",
      "CnAppointmentMailViewFactory",
      "CnSession",
      "CnHttpFactory",
      function (
        CnBaseModelFactory,
        CnAppointmentMailListFactory,
        CnAppointmentMailAddFactory,
        CnAppointmentMailViewFactory,
        CnSession,
        CnHttpFactory
      ) {
        var object = function (root) {
          CnBaseModelFactory.construct(this, module);
          this.addModel = CnAppointmentMailAddFactory.instance(this);
          this.listModel = CnAppointmentMailListFactory.instance(this);
          this.viewModel = CnAppointmentMailViewFactory.instance(this, root);

          this.hasAllSites = function () {
            return CnSession.role.allSites;
          };

          // extend getMetadata
          this.getMetadata = async function () {
            await this.$$getMetadata();

            var [languageResponse, siteResponse] = await Promise.all([
              CnHttpFactory.instance({
                path: "language",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: {
                    where: { column: "active", operator: "=", value: true },
                    order: "name",
                    limit: 1000,
                  },
                },
              }).query(),

              CnHttpFactory.instance({
                path: "site",
                data: {
                  select: { column: ["id", "name"] },
                  modifier: { order: "name", limit: 1000 },
                },
              }).query(),
            ]);

            this.metadata.columnList.language_id.enumList =
              languageResponse.data.reduce((list, item) => {
                list.push({ value: item.id, name: item.name });
                return list;
              }, []);

            this.metadata.columnList.site_id.enumList =
              siteResponse.data.reduce((list, item) => {
                list.push({ value: item.id, name: item.name });
                return list;
              }, []);
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
