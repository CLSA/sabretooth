cenozoApp.defineModule({
  name: "vacancy",
  dependencies: ["appointment", "site"],
  models: ["add", "list", "view"],
  create: (module) => {
    angular.extend(module, {
      identifier: {},
      name: {
        singular: "vacancy",
        plural: "vacancies",
        possessive: "vacancy's",
      },
      columnList: {
        datetime: {
          type: "datetime",
          title: "Date & Time",
        },
        operators: {
          type: "string",
          title: "Operators",
        },
      },
      defaultOrder: {
        column: "datetime",
        reverse: true,
      },
    });

    module.addInputGroup("", {
      datetime: {
        title: "Date & Time",
        type: "datetime",
        minuteStep: 60,
        isConstant: function ($state, model) {
          return (
            angular.isUndefined(model.viewModel.record.appointments) ||
            0 < model.viewModel.record.appointments
          );
        },
        help: "Can only be changed if the vacancy has no appointments.",
      },
      operators: {
        title: "Operators",
        type: "string",
        format: "integer",
        minValue: 1,
        help: "How many operators are available at this time",
      },
      appointments: { type: "hidden" },
    });

    // determines the offset given a date (moment object) and timezone
    function getTimezoneOffset(date, timezone) {
      var offset = moment.tz.zone(timezone).utcOffset(date.unix());
      if (date.tz(timezone).isDST()) offset += -60; // adjust the appointment for daylight savings time
      return offset;
    }

    // converts vacancies into events
    function getEventFromVacancy(vacancy, timezone, vacancySize) {
      if (angular.isDefined(vacancy.start) && angular.isDefined(vacancy.end)) {
        return vacancy;
      } else {
        var offset = getTimezoneOffset(moment(vacancy.datetime), timezone);
        var remaining = vacancy.operators - vacancy.appointments;
        var color = "blue";
        if (0 == remaining) color = "gray";
        else if (0 > remaining) color = "red";

        // get the identifier now and not in the getIdentifier() function below
        var identifier = vacancy.getIdentifier();
        return {
          id: vacancy.id,
          getIdentifier: function () {
            return identifier;
          },
          title: vacancy.appointments + " of " + vacancy.operators + " booked",
          start: moment(vacancy.datetime).subtract(offset, "minutes"),
          end: moment(vacancy.datetime)
            .subtract(offset, "minutes")
            .add(vacancySize, "minutes"),
          color: color,
          offset: offset,
          operators: vacancy.operators,
          appointments: vacancy.appointments,
        };
      }
    }

    // add an extra operation for each of the appointment-based calendars the user has access to
    ["appointment", "vacancy"].forEach((name) => {
      var calendarModule = cenozoApp.module(name);
      if (angular.isDefined(calendarModule.actions.calendar)) {
        module.addExtraOperation("calendar", {
          title: calendarModule.subject.snake.replace("_", " ").ucWords(),
          operation: async function ($state, model) {
            await $state.go(name + ".calendar", {
              identifier: model.site.getIdentifier(),
            });
          },
          classes: "vacancy" == name ? "btn-warning" : undefined, // highlight current model
        });
      }
    });

    if (angular.isDefined(module.actions.calendar)) {
      module.addExtraOperation("list", {
        title: "Vacancy Calendar",
        operation: async function ($state, model) {
          await $state.go("vacancy.calendar", {
            identifier: model.site.getIdentifier(),
          });
        },
      });
    }

    /* ############################################################################################## */
    cenozo.providers.directive("cnVacancyAdd", [
      "CnVacancyModelFactory",
      "CnSession",
      function (CnVacancyModelFactory, CnSession) {
        return {
          templateUrl: module.getFileUrl("add.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            if (angular.isUndefined($scope.model))
              $scope.model = CnVacancyModelFactory.instance();

            var cnRecordAddScope = null;
            $scope.$on("cnRecordAdd ready", async function (event, data) {
              cnRecordAddScope = data;

              // set the datetime in the record and formatted record (if passed here from the calendar)
              await $scope.model.metadata.getPromise();
              if (angular.isDefined($scope.model.addModel.calendarDate)) {
                cnRecordAddScope.record.datetime = moment.tz(
                  $scope.model.addModel.calendarDate + " 12:00:00",
                  CnSession.user.timezone
                );
                cnRecordAddScope.formattedRecord.datetime =
                  CnSession.formatValue(
                    cnRecordAddScope.record.datetime,
                    "datetime",
                    true
                  );
                delete $scope.model.addModel.calendarDate;
              }
            });
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnVacancyList", [
      "CnVacancyModelFactory",
      function (CnVacancyModelFactory) {
        return {
          templateUrl: module.getFileUrl("list.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            // need to create a model instance since there is no "root"
            if (angular.isUndefined($scope.model))
              $scope.model = CnVacancyModelFactory.instance();
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnVacancyView", [
      "CnVacancyModelFactory",
      function (CnVacancyModelFactory) {
        return {
          templateUrl: module.getFileUrl("view.tpl.html"),
          restrict: "E",
          scope: { model: "=?" },
          controller: function ($scope) {
            // need to create a model instance since there is no "root"
            if (angular.isUndefined($scope.model))
              $scope.model = CnVacancyModelFactory.instance();
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.directive("cnVacancyCalendar", [
      "CnVacancyModelFactory",
      "CnAppointmentModelFactory",
      "CnSession",
      "CnHttpFactory",
      "CnModalConfirmFactory",
      "CnModalMessageFactory",
      "CnModalInputFactory",
      function (
        CnVacancyModelFactory,
        CnAppointmentModelFactory,
        CnSession,
        CnHttpFactory,
        CnModalConfirmFactory,
        CnModalMessageFactory,
        CnModalInputFactory
      ) {
        // Adds a block of vacancies between the start/end times (used below)
        async function createVacancyBlock(
          calendarElement,
          calendarModel,
          start,
          end,
          operators,
          revertFunc
        ) {
          var response = await CnHttpFactory.instance({
            path: "vacancy",
            data: {
              start_datetime: start.format(),
              end_datetime: end.format(),
              operators: operators,
            },
            onError: function (error) {
              CnModalMessageFactory.httpError(error);
              if (angular.isFunction(revertFunc)) revertFunc();
            },
          }).post();

          // now create event objects for the returned id array (they'll be in chronological order)
          var datetime = angular.copy(start);
          var eventList = [];
          response.data.forEach((newId, index) => {
            var object = {
              id: newId,
              getIdentifier: function () {
                return newId;
              },
              datetime: angular.copy(datetime),
              operators: operators,
              appointments: 0,
            };
            var newEvent = getEventFromVacancy(
              object,
              CnSession.user.timezone,
              CnSession.setting.vacancySize
            );
            datetime.add(CnSession.setting.vacancySize, "minutes");

            // add the new event to the event list and cache
            eventList.push(newEvent);
            calendarModel.cache.push(newEvent);
          });

          calendarElement.fullCalendar("renderEvents", eventList);
        }

        return {
          templateUrl: module.getFileUrl("calendar.tpl.html"),
          restrict: "E",
          scope: {
            model: "=?",
            preventSiteChange: "@",
          },
          controller: function ($scope, $element) {
            if (angular.isUndefined($scope.model))
              $scope.model = CnVacancyModelFactory.instance();
            $scope.model.calendarModel.heading =
              $scope.model.site.name.ucWords() + " Vacancy Calendar";

            var cnRecordCalendarScope = null;
            $scope.$on("cnRecordCalendar ready", async function (event, data) {
              cnRecordCalendarScope = data;

              // refresh the calendar EVERY time we see it
              await $scope.model.calendarModel.onCalendar(true);
              await cnRecordCalendarScope.refresh();
            });

            angular.extend($scope.model.calendarModel.settings, {
              eventOverlap: false,
              selectable:
                $scope.model.getAddEnabled() &&
                $scope.model.getEditEnabled() &&
                "vacancy" == $scope.model.getSubjectFromState(),
              editable:
                $scope.model.getAddEnabled() &&
                $scope.model.getEditEnabled() &&
                "vacancy" == $scope.model.getSubjectFromState(),
              selectHelper: true,
              select: async function (start, end, jsEvent, view) {
                // do not process selections in month mode
                if ("month" == view.type) return;

                var calendar = $element.find("div.calendar");

                // determine if the selection overlaps any events
                var overlap = false;
                var overlapEventList = calendar
                  .fullCalendar("clientEvents")
                  .filter((event) => event.start >= start && event.end <= end);

                try {
                  if (0 == overlapEventList.length) {
                    // the selection did not overlap any events, create vacancies to fill the selection box
                    if (
                      CnSession.setting.vacancySize > end.diff(start, "minutes")
                    ) {
                      await CnModalMessageFactory.instance({
                        title: "Unable to create vacancies",
                        message:
                          "There was a problem creating vacancies, please try again.",
                        error: true,
                      }).show();
                    } else {
                      var offset = getTimezoneOffset(
                        start,
                        CnSession.user.timezone
                      );
                      start.add(offset, "minutes");
                      end.add(offset, "minutes");

                      var response = await CnModalInputFactory.instance({
                        title: "Create Vacancy Block",
                        message:
                          "Would you like to create a block of vacancies from " +
                          CnSession.formatValue(start, "datetime") +
                          " to " +
                          CnSession.formatValue(end, "datetime") +
                          "?\n\n" +
                          "If you wish to proceed please provide the number of operators the vacancy has available:",
                        required: true,
                        format: "integer",
                        minValue: 1,
                        value: 1,
                      }).show();

                      if (false !== response && 0 < response)
                        await createVacancyBlock(
                          calendar,
                          $scope.model.calendarModel,
                          start,
                          end,
                          response
                        );
                    }
                  } else {
                    // the selection overlaps with some event, only delete vacancies which have no appointments
                    var removeEventList = overlapEventList.filter(
                      (event) => 0 == event.appointments
                    );

                    if (0 == removeEventList) {
                      await CnModalMessageFactory.instance({
                        title: "Cannot Delete Vacancies",
                        message:
                          "None of the " +
                          overlapEventList.length +
                          " vacancies you have selected can be " +
                          "deleted because they already have at least one appointment scheduled.",
                        error: true,
                      }).show();
                    } else {
                      var message =
                        "Would you like to delete the " +
                        removeEventList.length +
                        " vacancies which you have selected?";
                      if (removeEventList.length != overlapEventList.length)
                        message +=
                          "\n\nNote: only vacancies which do not have any appointments will be deleted.";

                      var response = await CnModalConfirmFactory.instance({
                        title: "Delete Vacancies?",
                        message: message,
                      }).show();

                      if (response) {
                        // we use a special version of the post function that deletes lists of ids in a single request
                        await CnHttpFactory.instance({
                          path: "vacancy",
                          data: {
                            delete_ids: removeEventList.map((event) =>
                              event.getIdentifier()
                            ),
                          },
                        }).post();
                        removeEventList.forEach((event) =>
                          calendar.fullCalendar("removeEvents", event.id)
                        );
                      }
                    }
                  }
                } finally {
                  calendar.fullCalendar("unselect");
                }
              },
              eventDrop: async function (event, delta, revertFunc) {
                // time is in local timezone, convert back to UTC
                var datetime = angular.copy(event.start);
                datetime.add(event.offset, "minutes");

                var cacheEvent =
                  $scope.model.calendarModel.cache.findByProperty(
                    "id",
                    event.id
                  );
                await CnHttpFactory.instance({
                  path: "vacancy/" + event.getIdentifier(),
                  data: { datetime: datetime.format() },
                  onError: function (error) {
                    CnModalMessageFactory.httpError(error);
                    revertFunc();
                  },
                }).patch();

                // now update this event in the vacancy cache
                cacheEvent.start = event.start;
                cacheEvent.end = event.end;
              },
              eventResize: async function (event, delta, revertFunc) {
                var calendar = $element.find("div.calendar");

                // time is in local timezone, convert back to UTC
                var datetime = angular.copy(event.start);
                datetime.add(event.offset, "minutes");
                var end = angular.copy(event.end);
                end.add(event.offset, "minutes");

                if (
                  CnSession.setting.vacancySize >= end.diff(datetime, "minutes")
                ) {
                  await CnModalMessageFactory.instance({
                    title: "Unable to extend vacancy",
                    message:
                      "There was a problem extending the vacancy, please try again.",
                    error: true,
                  }).show();

                  revertFunc();
                } else {
                  // convert the extended event back to vacancy-size minutes
                  var revertEnd = angular.copy(event.start);
                  revertEnd.add(CnSession.setting.vacancySize, "minutes");
                  event.end = revertEnd;

                  // re-render the event show it displays the new details
                  calendar.fullCalendar("removeEvents", event.id);
                  calendar.fullCalendar("renderEvent", event);

                  // create additional vacancy-size minute increments to fill up the new vacancy
                  datetime.add(CnSession.setting.vacancySize, "minutes"); // skip the first since it already exists
                  await createVacancyBlock(
                    calendar,
                    $scope.model.calendarModel,
                    datetime,
                    end,
                    event.operators,
                    revertFunc
                  );
                }
              },
            });
          },
          link: function (scope) {
            // factory name -> object map used below
            var factoryList = {
              appointment: CnAppointmentModelFactory,
              vacancy: CnVacancyModelFactory,
            };

            // synchronize appointment/vacancy-based calendars
            scope.$watch("model.calendarModel.currentDate", function (date) {
              Object.keys(factoryList)
                .filter((name) =>
                  angular.isDefined(cenozoApp.moduleList[name].actions.calendar)
                )
                .forEach((name) => {
                  var calendarModel = factoryList[name].forSite(
                    scope.model.site
                  ).calendarModel;
                  if (!calendarModel.currentDate.isSame(date, "day"))
                    calendarModel.currentDate = date;
                });
            });
            scope.$watch("model.calendarModel.currentView", function (view) {
              Object.keys(factoryList)
                .filter((name) =>
                  angular.isDefined(cenozoApp.moduleList[name].actions.calendar)
                )
                .forEach((name) => {
                  var calendarModel = factoryList[name].forSite(
                    scope.model.site
                  ).calendarModel;
                  if (calendarModel.currentView != view)
                    calendarModel.currentView = view;
                });
            });
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnVacancyAddFactory", [
      "CnBaseAddFactory",
      "CnSession",
      function (CnBaseAddFactory, CnSession) {
        var object = function (parentModel) {
          CnBaseAddFactory.construct(this, parentModel);

          // add the new vacancy's events to the calendar cache
          this.onAdd = async function (record) {
            await this.$$onAdd(record);
            record.getIdentifier = function () {
              return parentModel.getIdentifierFromRecord(record);
            };

            // fill in the user name so that it shows in the calendar
            parentModel.calendarModel.cache.push(
              getEventFromVacancy(
                record,
                CnSession.user.timezone,
                CnSession.setting.vacancySize
              )
            );
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
    cenozo.providers.factory("CnVacancyCalendarFactory", [
      "CnBaseCalendarFactory",
      "CnSession",
      function (CnBaseCalendarFactory, CnSession) {
        var object = function (parentModel) {
          CnBaseCalendarFactory.construct(this, parentModel);

          // remove the day click event
          delete this.settings.dayClick;

          // extend onCalendar to transform vacancies into events
          this.onCalendar = async function (
            replace,
            minDate,
            maxDate,
            ignoreParent
          ) {
            // we must get the load dates before calling $$onCalendar
            var loadMinDate = this.getLoadMinDate(replace, minDate);
            var loadMaxDate = this.getLoadMaxDate(replace, maxDate);
            // note that we ignore the ignoreParent parameter and always ignore the parent
            await this.$$onCalendar(replace, minDate, maxDate, true);
            this.cache.forEach((item, index, array) => {
              array[index] = getEventFromVacancy(
                item,
                CnSession.user.timezone,
                CnSession.setting.vacancySize
              );
            });
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
    cenozo.providers.factory("CnVacancyListFactory", [
      "CnBaseListFactory",
      function (CnBaseListFactory) {
        var object = function (parentModel) {
          CnBaseListFactory.construct(this, parentModel);

          // remove the deleted vacancy from the calendar cache
          this.onDelete = async function (record) {
            await this.$$onDelete(record);

            parentModel.calendarModel.cache =
              parentModel.calendarModel.cache.filter(
                (e) => e.getIdentifier() != record.getIdentifier()
              );
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
    cenozo.providers.factory("CnVacancyViewFactory", [
      "CnBaseViewFactory",
      "CnSession",
      function (CnBaseViewFactory, CnSession) {
        var object = function (parentModel, root) {
          CnBaseViewFactory.construct(this, parentModel, root);

          // remove the deleted vacancy's events from the calendar cache
          this.onDelete = async function () {
            await this.$$onDelete();
            parentModel.calendarModel.cache =
              parentModel.calendarModel.cache.filter(
                (e) => e.getIdentifier() != this.record.getIdentifier()
              );
          };

          this.onPatch = async function (data) {
            await this.$$onPatch(data);

            // rebuild the event for this record
            parentModel.calendarModel.cache.some((e, index, array) => {
              if (e.getIdentifier() == this.record.getIdentifier()) {
                array[index] = getEventFromVacancy(
                  this.record,
                  CnSession.user.timezone,
                  CnSession.setting.vacancySize
                );
                return true;
              }
            });
          };
        };
        return {
          instance: function (parentModel, root) {
            return new object(parentModel, root);
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnVacancyModelFactory", [
      "CnBaseModelFactory",
      "CnVacancyAddFactory",
      "CnVacancyCalendarFactory",
      "CnVacancyListFactory",
      "CnVacancyViewFactory",
      "CnSession",
      "$state",
      function (
        CnBaseModelFactory,
        CnVacancyAddFactory,
        CnVacancyCalendarFactory,
        CnVacancyListFactory,
        CnVacancyViewFactory,
        CnSession,
        $state
      ) {
        var object = function (site) {
          if (!angular.isObject(site) || angular.isUndefined(site.id))
            throw new Error(
              "Tried to create CnVacancyModel without specifying the site."
            );

          CnBaseModelFactory.construct(this, module);

          angular.extend(this, {
            addModel: CnVacancyAddFactory.instance(this),
            calendarModel: CnVacancyCalendarFactory.instance(this),
            listModel: CnVacancyListFactory.instance(this),
            viewModel: CnVacancyViewFactory.instance(
              this,
              site.id == CnSession.site.id
            ),
            viewTitle: "Vacancy Calendar",
            site: site,

            getAddEnabled: function () {
              return "calendar" == this.getActionFromState()
                ? this.$$getAddEnabled()
                : false;
            },

            // replace view-list with view-calendar
            transitionToParentListState: async function (subject) {
              if ("vacancy" == subject) {
                // switch to/from calendar/list states
                var name =
                  "vacancy.calendar" == $state.current.name
                    ? "vacancy.list"
                    : "vacancy.calendar";
                var param =
                  "vacancy.calendar" == $state.current.name
                    ? undefined
                    : { identifier: this.site.getIdentifier() };
                await $state.go(name, param);
              } else {
                await this.$$transitionToParentListState(subject);
              }
            },

            transitionToListState: async function () {
              await this.transitionToParentListState("vacancy");
            },

            // customize service data
            getServiceData: function (type, columnRestrictLists) {
              var data = this.$$getServiceData(type, columnRestrictLists);
              if ("calendar" == type) data.restricted_site_id = this.site.id;
              return data;
            },
          });

          // define the datetime interval
          this.module.inputGroupList.findByProperty(
            "title",
            ""
          ).inputList.datetime.minuteStep = CnSession.setting.vacancySize;
        };

        // get the siteColumn to be used by a site's identifier
        var siteModule = cenozoApp.module("site");
        var siteColumn = angular.isDefined(siteModule.identifier.column)
          ? siteModule.identifier.column
          : "id";

        return {
          siteInstanceList: {},
          forSite: function (site) {
            // redirect if we can't find the site
            if (!angular.isObject(site)) {
              $state.go("error.404");
              throw (
                'Cannot find user matching identifier "' +
                site +
                '", redirecting to 404.'
              );
            }

            if (angular.isUndefined(this.siteInstanceList[site.id])) {
              if (angular.isUndefined(site.getIdentifier))
                site.getIdentifier = function () {
                  return siteColumn + "=" + this[siteColumn];
                };
              this.siteInstanceList[site.id] = new object(site);
            }
            return this.siteInstanceList[site.id];
          },
          instance: function () {
            var site = null;
            if ("calendar" == $state.current.name.split(".")[1]) {
              if (angular.isDefined($state.params.identifier)) {
                var identifier = $state.params.identifier.split("=");
                if (2 == identifier.length)
                  site = CnSession.siteList.findByProperty(
                    identifier[0],
                    identifier[1]
                  );
              }
            } else {
              site = CnSession.site;
            }
            return this.forSite(site);
          },
        };
      },
    ]);
  },
});
