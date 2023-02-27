cenozoApp.extendModule({
  name: "assignment",
  dependencies: "participant",
  create: (module) => {
    module.identifier.parent.friendly = "qnaire";

    module.addInput(
      "",
      "qnaire",
      {
        column: "script.name",
        title: "Questionnaire",
        type: "string",
        isConstant: true,
      },
      "participant"
    );
    module.addInput(
      "",
      "queue",
      {
        column: "queue.title",
        title: "Queue",
        type: "string",
        isConstant: true,
      },
      "site"
    );

    /* ############################################################################################## */
    cenozo.providers.directive("cnAssignmentControl", [
      "CnAssignmentControlFactory",
      "CnSession",
      "$window",
      function (CnAssignmentControlFactory, CnSession, $window) {
        return {
          templateUrl: cenozoApp.getFileUrl("assignment", "control.tpl.html"),
          restrict: "E",
          controller: async function ($scope) {
            $scope.model = CnAssignmentControlFactory.instance();
            await $scope.model.onLoad(false); // breadcrumbs are handled by the service
          },
          link: function (scope) {
            // update the script list whenever we regain focus since there may have been script activity
            var win = angular.element($window).on("focus", async () => {
              scope.model.loadScriptList();
              scope.model.updatePageProgress();
            });
            scope.$on("$destroy", function () {
              win.off("focus");
            });

            // close the session's script window whenever this page is unloaded (refreshed or closed)
            $window.onunload = function () {
              CnSession.closeScript();
            };
          },
        };
      },
    ]);

    /* ############################################################################################## */
    cenozo.providers.factory("CnAssignmentControlFactory", [
      "CnParticipantModelFactory",
      "CnScriptLauncherFactory",
      "CnModalMessageFactory",
      "CnModalConfirmFactory",
      "CnSession",
      "CnHttpFactory",
      "$state",
      "$window",
      function (
        CnParticipantModelFactory,
        CnScriptLauncherFactory,
        CnModalMessageFactory,
        CnModalConfirmFactory,
        CnSession,
        CnHttpFactory,
        $state,
        $window
      ) {
        var object = function (root) {
          angular.extend(this, {
            scriptLauncher: null,
            participantModel: CnParticipantModelFactory.instance(),

            reset: function () {
              angular.extend(this, {
                proxyInterview: CnSession.setting.proxy,
                hasIdentifier: null != CnSession.application.identifier,
                assignment: null,
                prevAssignment: null,
                participant: null,
                phoneList: null,
                activePhoneCall: false,
                qnaireList: null,
                activeQnaire: null,
                lastQnaire: null,
                isScriptListLoading: false,
                scriptList: null,
                activeScript: null,
                phoneCallStatusList: null,
                phoneCallList: null,
                isAssignmentLoading: false,
                isForbidden: false,
                isPrevAssignmentLoading: false,
              });
            },

            canChangeInterviewMethod: function () {
              return this.participantModel.isRole(
                "administrator",
                "helpline",
                "operator+",
                "supervisor"
              );
            },

            setInterviewMethod: async function () {
              var self = this;
              try {
                this.changingInterviewMethod = true;
                await CnHttpFactory.instance({
                  path: "interview/" + this.assignment.interview_id,
                  data: { method: this.assignment.interview_method },
                  onError: function (error) {
                    self.assignment.interview_method =
                      "phone" == self.assignment.interview_method
                        ? "web"
                        : "phone";
                    CnModalMessageFactory.httpError(error);
                  },
                }).patch();
              } finally {
                this.changingInterviewMethod = false;
              }
            },

            // start a new assignment with a participant (provided by record) or whoever is available next
            beginAssignment: async function (record) {
              // attempt to assign the participant to the user
              var response = await CnModalConfirmFactory.instance({
                title: "Begin Assignment",
                message: angular.isDefined(record)
                  ? "Are you sure you wish to start a new assignment with participant " +
                    record.uid +
                    "?"
                  : "Are you sure you wish to start a new assignment with the next available participant?",
              }).show();

              if (response) {
                try {
                  this.isAssignmentLoading = true; // show loading screen right away
                  var self = this;
                  await CnHttpFactory.instance({
                    path: "assignment?operation=open",
                    data: angular.isDefined(record)
                      ? { participant_id: record.id }
                      : undefined,
                    onError: async function (error) {
                      if (408 == error.status) {
                        // 408 means there are currently no participants available (this only happens with no record)
                        await CnModalMessageFactory.instance({
                          title: "No participants available",
                          message: error.data,
                          error: true,
                        }).show();
                        await self.onLoad();
                      } else if (409 == error.status) {
                        // 409 means there is a conflict (the assignment can't be made)
                        await CnModalMessageFactory.instance({
                          title: angular.isDefined(record)
                            ? "Unable to start assignment with " + record.uid
                            : "Unable to start a new assignment",
                          message: error.data,
                          error: true,
                        }).show();
                        await self.onLoad();
                      } else {
                        await CnModalMessageFactory.httpError(error);
                      }
                    },
                  }).post();

                  await this.onLoad();
                } finally {
                  this.isAssignmentLoading = false;
                }
              }
            },

            onLoad: async function (closeScript) {
              if (angular.isUndefined(closeScript)) closeScript = true;
              this.reset();

              try {
                this.isAssignmentLoading = true;
                this.isPrevAssignmentLoading = true;
                if (closeScript) CnSession.closeScript();

                var column = [
                  "id",
                  "interview_id",
                  "start_datetime",
                  "has_alternate_types",
                  {
                    table: "participant",
                    column: "id",
                    alias: "participant_id",
                  },
                  { table: "qnaire", column: "id", alias: "qnaire_id" },
                  { table: "qnaire", column: "web_version", type: "boolean" },
                  { table: "script", column: "id", alias: "script_id" },
                  { table: "script", column: "name", alias: "qnaire" },
                  { table: "queue", column: "title", alias: "queue" },
                  {
                    table: "interview",
                    column: "method",
                    alias: "interview_method",
                  },
                ];

                if (CnSession.application.checkForMissingHin)
                  column.push("missing_hin");
                if (this.proxyInterview) column.push("use_decision_maker");

                var self = this;
                var response = await CnHttpFactory.instance({
                  path: "assignment/0",
                  data: { select: { column: column } },
                  onError: async function (error) {
                    await CnSession.updateData();
                    self.assignment = null;
                    self.participant = null;
                    self.isPrevAssignmentLoading = false;
                    self.isForbidden = false;
                    if (307 == error.status) {
                      // 307 means the user has no active assignment, so load the participant select list
                      CnSession.alertHeader = undefined;
                      self.participantModel.listModel.afterList(function () {
                        if (
                          self.participantModel.isRole(
                            "operator",
                            "operator+"
                          ) &&
                          0 < self.participantModel.listModel.cache.length
                        ) {
                          self.participantModel.listModel.heading =
                            "Participant Selection List (" +
                            self.participantModel.listModel.cache[0].queue +
                            ")";
                        }
                        CnSession.setBreadcrumbTrail([
                          { title: "Assignment" },
                          { title: "Select" },
                        ]);
                      });
                    } else if (403 == error.status) {
                      CnSession.alertHeader =
                        "You are currently in an assignment";
                      CnSession.setBreadcrumbTrail([
                        { title: "Assignment" },
                        { title: "Wrong Site" },
                      ]);
                      self.isForbidden = true;
                    } else {
                      CnModalMessageFactory.httpError(error);
                    }
                  },
                }).get();

                this.assignment = response.data;

                CnSession.alertHeader = "You are currently in an assignment";
                await CnSession.updateData();

                // show a popup if the participant is missing HIN data
                // Note: this will only show if the participant has consented to provide HIN but hasn't provided an HIN number
                if (
                  CnSession.application.checkForMissingHin &&
                  this.assignment.missing_hin
                ) {
                  CnModalMessageFactory.instance({
                    title: "Missing HIN",
                    message:
                      "The participant has consented to provide their Health Insurance Number (HIN) " +
                      "but their number is not on file.\n\n" +
                      "Please ask the participant to provide their HIN number.  The details can be added in the participant's file " +
                      'under the "HIN List" section.',
                  }).show();
                }

                // get the assigned participant's details
                var response = await CnHttpFactory.instance({
                  path: "participant/" + this.assignment.participant_id,
                  data: {
                    select: {
                      column: [
                        "id",
                        "uid",
                        "honorific",
                        "first_name",
                        "other_name",
                        "last_name",
                        "global_note",
                        {
                          table: "participant_identifier",
                          column: "value",
                          alias: "study_id",
                        },
                        {
                          table: "language",
                          column: "code",
                          alias: "language_code",
                        },
                        {
                          table: "language",
                          column: "name",
                          alias: "language",
                        },
                      ],
                    },
                  },
                  onError: function () {
                    console.error("Failed to get participant's details");
                  },
                }).get();
                this.participant = response.data;

                var self = this;
                this.participant.getIdentifier = function () {
                  return self.participantModel.getIdentifierFromRecord(
                    self.participant
                  );
                };

                CnSession.setBreadcrumbTrail([
                  { title: "Assignment" },
                  { title: this.participant.uid },
                ]);
              } finally {
                this.isAssignmentLoading = false;
              }

              if (null === this.qnaireList) {
                // get the qnaire list and store the current and last qnaires
                var qnaireResponse = await CnHttpFactory.instance({
                  path: "qnaire",
                  data: {
                    select: {
                      column: [
                        "id",
                        "rank",
                        "script_id",
                        "delay_offset",
                        "delay_unit",
                        "allow_missing_consent",
                      ],
                    },
                    modifier: { order: "rank" },
                  },
                }).query();

                this.qnaireList = qnaireResponse.data;
                var len = this.qnaireList.length;
                if (0 < len) {
                  this.activeQnaire = this.qnaireList.findByProperty(
                    "id",
                    this.assignment.qnaire_id
                  );
                  this.lastQnaire = this.qnaireList[len - 1];
                }

                // now load the script list and update the page progress
                this.loadScriptList();
                this.updatePageProgress();
              }

              const [
                phoneCallResponse,
                assignmentResponse,
                phoneResponse,
                phoneCallStatusResponse,
              ] = await Promise.all([
                CnHttpFactory.instance({
                  path: "assignment/0/phone_call",
                  data: {
                    select: {
                      column: [
                        "end_datetime",
                        "status",
                        "person",
                        { table: "phone", column: "participant_id" },
                        { table: "phone", column: "alternate_id" },
                        { table: "phone", column: "rank" },
                        { table: "phone", column: "type" },
                        { table: "phone", column: "number" },
                      ],
                    },
                  },
                }).query(),

                CnHttpFactory.instance({
                  path:
                    "participant/" +
                    this.assignment.participant_id +
                    "/interview/" +
                    this.assignment.interview_id +
                    "/assignment",
                  data: {
                    select: {
                      column: [
                        "start_datetime",
                        "end_datetime",
                        "phone_call_count",
                        { table: "last_phone_call", column: "status" },
                        { table: "user", column: "first_name" },
                        { table: "user", column: "last_name" },
                        { table: "user", column: "name" },
                      ],
                    },
                    modifier: {
                      order: { start_datetime: true },
                      offset: 1,
                      limit: 1,
                    },
                  },
                }).query(),

                await CnHttpFactory.instance({
                  path: "participant/" + this.assignment.participant_id + "/phone" + (
                    this.proxyInterview || this.assignment.has_alternate_types ? "?include_alternates=1" : ''
                  ),
                  data: {
                    select: {
                      column: [
                        "id",
                        "rank",
                        "type",
                        "number",
                        "international",
                        "note",
                      ],
                    },
                    modifier: {
                      where: {
                        column: "phone.active",
                        operator: "=",
                        value: true,
                      },
                      order: ["rank"],
                    },
                  },
                }).query(),

                CnHttpFactory.instance({ path: "phone_call", }).head(),
              ]);

              this.phoneCallList = phoneCallResponse.data;
              var len = this.phoneCallList.length;
              this.activePhoneCall =
                0 < len && null === this.phoneCallList[len - 1].end_datetime
                  ? this.phoneCallList[len - 1]
                  : null;

              this.prevAssignment = 1 == assignmentResponse.data.length ? assignmentResponse.data[0] : null;
              this.isPrevAssignmentLoading = false;

              this.phoneList = [];
              var lastPerson = null;
              phoneResponse.data.forEach((phone) => {
                phone.newPerson = false;

                if (phone.person != lastPerson) {
                  var newPhone = angular.copy(phone);
                  newPhone.newPerson = true;
                  this.phoneList.push(newPhone);
                } else {
                  this.phoneList.push(phone);
                }
                lastPerson = phone.person;
              });

              this.phoneCallStatusList = cenozo.parseEnumList(
                angular.fromJson(phoneCallStatusResponse.headers("Columns")).status
              );
            },

            changeSiteRole: function () {
              CnSession.showSiteRoleModal();
            },

            openNotes: async function () {
              if (null != this.participant)
                await $state.go("participant.notes", {
                  identifier: this.participant.getIdentifier(),
                });
            },

            openHistory: async function () {
              if (null != this.participant)
                await $state.go("participant.history", {
                  identifier: this.participant.getIdentifier(),
                });
            },

            /** 
             * The object can be undefined, in which case the current participant's timezone will be used.
             * Otherwise it should be the alternate {alternate_id:...} or participant {participant_id:...}
             */
            useTimezone: async function (obj) {
              if (angular.isUndefined(obj)) {
                if (null != this.participant) {
                  obj = {participant_id: this.participant.id};
                }
              }

              if (angular.isDefined(obj)) {
                await CnSession.setTimezone(obj);
                await $state.go("self.wait");
                $window.location.reload();
              }
            },

            updatePageProgress: async function () {
              if (null == this.assignment) return;

              var response = await CnHttpFactory.instance({
                path: "assignment/0?update_data=1",
                data: { select: { column: "page_progress" } },
              }).get();
              this.assignment.page_progress = response.data.page_progress;
            },

            loadScriptList: async function () {
              if (null == this.assignment) return;

              try {
                this.isScriptListLoading = true;

                if (null != this.participant) {
                  var response = await CnHttpFactory.instance({
                    path: "participant/" + this.assignment.participant_id,
                    data: {
                      select: {
                        column: [
                          { table: "hold_type", column: "name", alias: "hold" },
                          {
                            table: "proxy_type",
                            column: "name",
                            alias: "proxy",
                          },
                        ],
                      },
                    },
                  }).get();

                  this.participant.withdrawn =
                    "Withdrawn" == response.data.hold;
                  this.participant.proxy = null != response.data.proxy;
                }

                var response = await CnHttpFactory.instance({
                  path:
                    "application/0/script?participant_id=" +
                    this.assignment.participant_id,
                  data: {
                    modifier: { order: ["repeated", "name"] },
                    select: {
                      column: [
                        "id",
                        "name",
                        "repeated",
                        "supporting",
                        "url",
                        "description",
                        {
                          table: "started_event",
                          column: "datetime",
                          alias: "started_datetime",
                        },
                        {
                          table: "finished_event",
                          column: "datetime",
                          alias: "finished_datetime",
                        },
                      ],
                    },
                  },
                }).query();

                // put qnaire scripts in separate list and only include the current qnaire script in the main list
                this.scriptList = [];
                this.qnaireScriptList = [];
                response.data.forEach((item) => {
                  if (
                    angular.isArray(this.qnaireList) &&
                    null != this.qnaireList.findByProperty("script_id", item.id)
                  ) {
                    this.qnaireScriptList.push(item);
                    if (item.id == this.assignment.script_id)
                      this.scriptList.unshift(item);
                  } else {
                    this.scriptList.push(item);
                  }
                });

                if (0 == this.scriptList.length) {
                  this.activeScript = null;
                } else {
                  if (
                    null == this.activeScript ||
                    null ==
                      this.scriptList.findByProperty("id", this.activeScript.id)
                  ) {
                    this.activeScript = this.scriptList[0];
                  } else {
                    var activeScriptName = this.activeScript.name;
                    this.scriptList.forEach((item) => {
                      if (activeScriptName == item.name)
                        this.activeScript = item;
                    });
                  }
                }
              } finally {
                this.isScriptListLoading = false;
              }
            },

            scriptLauncherBusy: false,
            launchScript: async function (script) {
              var cannotProceedMessage = null;

              // if the application has a consent type then check if the script can proceed without consent or not
              if (null != CnSession.application.consentTypeId) {
                var matchingQnaire = this.qnaireList.findByProperty(
                  "script_id",
                  script.id
                );
                if (
                  null != matchingQnaire &&
                  !matchingQnaire.allow_missing_consent
                ) {
                  try {
                    var identifier = [
                      "type=last",
                      "consent_type_id=" + CnSession.application.consentTypeId,
                    ].join(";");
                    var response = await CnHttpFactory.instance({
                      path: [
                        "participant",
                        this.assignment.participant_id,
                        "consent",
                        identifier,
                      ].join("/"),
                      onError: function (error) {
                        if (404 == error.status) {
                          // if we get a 404 then the participant does not have consent to proceed with this qnaire
                          cannotProceedMessage =
                            "The participant cannot continue the interview as they have not consented to participate in the study.";
                        } else {
                          CnModalMessageFactory.httpError(error);
                        }
                      },
                    }).get();

                    if (!response.data.accept)
                      cannotProceedMessage =
                        "The participant cannot continue the interview as they have declined to participate in the study.";
                  } catch (err) {
                    // handled by onError function above
                  }
                }
              }

              if (
                null == cannotProceedMessage &&
                0 < this.activeQnaire.delay_offset &&
                1 < this.activeQnaire.rank
              ) {
                var previousQnaire = this.qnaireList.findByProperty(
                  "rank",
                  this.activeQnaire.rank - 1
                );
                var delayUntil = moment(
                  this.qnaireScriptList.findByProperty(
                    "id",
                    previousQnaire.script_id
                  ).finished_datetime
                ).add(
                  this.activeQnaire.delay_offset,
                  this.activeQnaire.delay_unit
                );

                if (delayUntil.isAfter(moment(new Date()), "days")) {
                  cannotProceedMessage =
                    "The participant cannot continue the interview process until " +
                    delayUntil.format("dddd, MMMM Do") +
                    ".  Please end your assignment now, the participant will become available for assignment after the " +
                    "delay has ended.";
                }
              }

              if (null != cannotProceedMessage) {
                await CnModalMessageFactory.instance({
                  title: "Interview Cannot Proceed",
                  message: cannotProceedMessage,
                }).show();
              } else {
                try {
                  this.scriptLauncherBusy = true;

                  var urlParams = { show_hidden: 1 };
                  if (this.activePhoneCall.alternate_id)
                    urlParams.alternate_id = this.activePhoneCall.alternate_id;
                  this.scriptLauncher = CnScriptLauncherFactory.instance({
                    script: script,
                    identifier: "uid=" + this.participant.uid,
                    lang: this.participant.language_code,
                  });
                  await this.scriptLauncher.launch(urlParams);
                  await this.loadScriptList();
                  this.updatePageProgress(); // no need to await
                } finally {
                  this.scriptLauncherBusy = false;
                }
              }
            },

            advanceQnaire: async function () {
              await CnHttpFactory.instance({
                path: "assignment/0?operation=advance",
                data: {},
              }).patch();
              await this.onLoad();
            },

            startCall: async function (phone) {
              // start by updating the voip status
              try {
                CnSession.updateVoip();
              } finally {
                var call = false;

                if (
                  !CnSession.voip.enabled ||
                  (angular.isObject(CnSession.voip.info) &&
                    "UNKNOWN" == CnSession.voip.info.status &&
                    CnSession.setting.callWithoutWebphone)
                ) {
                  call = true;
                } else {
                  if (!CnSession.voip.info) {
                    if (!CnSession.setting.callWithoutWebphone) {
                      CnModalMessageFactory.instance({
                        title: "Webphone Not Found",
                        message:
                          "You cannot start a call without a webphone connection. " +
                          'To use the built-in telephone system click on the "Webphone" link under the ' +
                          '"Utilities" submenu and make sure the webphone client is connected.',
                        error: true,
                      }).show();
                    } else if (!phone.international) {
                      var response = await CnModalConfirmFactory.instance({
                        title: "Webphone Not Found",
                        message:
                          "You are about to place a call with no webphone connection. " +
                          "If you choose to proceed you will have to contact the participant without the use " +
                          "of the software-based telephone system. " +
                          'If you wish to use the built-in telephone system click "No", then click on the ' +
                          '"Webphone" link under the "Utilities" submenu to connect to the webphone.\n\n' +
                          "Do you wish to proceed without a webphone connection?",
                      }).show();
                      call = response;
                    }
                  } else {
                    if (phone.international) {
                      var response = await CnModalConfirmFactory.instance({
                        title: "International Phone Number",
                        message:
                          "The phone number you are about to call is international. " +
                          "The VoIP system cannot place international calls so if you choose to proceed you " +
                          "will have to contact the participant without the use of the software-based " +
                          "telephone system.\n\n" +
                          "Do you wish to proceed without a webphone connection?",
                      }).show();
                      call = response;
                    } else {
                      var response = await CnHttpFactory.instance({
                        path: "voip",
                        data: { phone_id: phone.id },
                      }).post();

                      if (201 == response.status) {
                        call = true;
                      } else {
                        CnModalMessageFactory.instance({
                          title: "Webphone Error",
                          message:
                            "The webphone was unable to place your call, please try again. " +
                            "If this problem persists then please contact support.",
                          error: true,
                        }).show();
                      }
                    }
                  }
                }

                if (call) {
                  await CnHttpFactory.instance({
                    path: "phone_call?operation=open",
                    data: { phone_id: phone.id },
                  }).post();
                  await this.onLoad();
                }
              }
            },

            endCall: async function (status) {
              if (
                CnSession.voip.enabled &&
                CnSession.voip.info &&
                !this.activePhoneCall.international
              ) {
                try {
                  await CnHttpFactory.instance({
                    path: "voip/0",
                    onError: function (error) {
                      if (404 == error.status) {
                        // ignore 404 errors, it just means there was no phone call found to hang up
                      } else {
                        CnModalMessageFactory.httpError(error);
                      }
                    },
                  }).delete();
                } catch (error) {
                  // handled by onError above
                }
              }

              await CnHttpFactory.instance({
                path: "phone_call/0?operation=close",
                data: { status: status },
              }).patch();
              await this.onLoad();
            },

            endAssignment: async function () {
              if (null != this.assignment) {
                var self = this;
                var response = await CnHttpFactory.instance({
                  path: "assignment/0",
                  onError: function (error) {
                    if (307 == error.status) {
                      // 307 means the user has no active assignment, so just refresh the page data
                      self.onLoad();
                    } else {
                      CnModalMessageFactory.httpError(error);
                    }
                  },
                }).get();

                // make absolute sure that the script has been closed
                CnSession.closeScript();
                await CnHttpFactory.instance({
                  path: "assignment/0?operation=close",
                  data: {},
                }).patch();
                await this.onLoad();
              }
            },
          });

          angular.extend(this.participantModel, {
            // map assignment-control query parameters to participant-list
            queryParameterSubject: "assignment",

            // override model functions
            getServiceCollectionPath: function () {
              return "participant";
            },

            getServiceData: function (type, columnRestrictList) {
              var data = this.$$getServiceData(type, columnRestrictList);
              data.assignment = true;
              return data;
            },
          });

          var self = this;
          angular.extend(this.participantModel.listModel, {
            onList: async function (replace) {
              await this.$$onList(replace);
            },

            // override the default column order for the participant list to rank
            order: { column: "queue.rank", reverse: false },

            // override the default order and set the heading
            heading: "Participant Selection List",

            // override the onChoose function
            onSelect: async function (record) {
              await self.beginAssignment(record);
            },
          });

          this.reset();

          async function init(object) {
            await CnSession.promise;
            object.application = CnSession.application.title;

            // add additional columns to the model
            var index = 0;
            object.participantModel.addColumn(
              "rank",
              { title: "Rank", column: "queue.rank", type: "rank" },
              index++
            );
            object.participantModel.addColumn(
              "queue",
              { title: "Queue", column: "queue.name", type: "string" },
              index++
            );
            object.participantModel.addColumn(
              "qnaire",
              { title: "Questionnaire", column: "script.name" },
              index++
            );
            object.participantModel.addColumn(
              "page_progress",
              { title: "Page Progress" },
              index++
            );
            object.participantModel.addColumn(
              "language",
              { title: "Language", column: "language.name" },
              index++
            );
            object.participantModel.addColumn("availability", {
              title: "Availability",
              column: "availability_type.name",
            });

            // add the reserved row as a hidden column to be used for highlighting reserved appointments
            object.participantModel.addColumn("reserved", {
              type: "hidden",
              highlight: true,
            });

            // if there are any alternate-types with consents then add a column for each one
            var response = await CnHttpFactory.instance({
              path: "alternate_type",
              data: {
                modifier: {
                  join: {
                    table: "qnaire_has_alternate_type",
                    onleft: "alternate_type.id",
                    onright: "qnaire_has_alternate_type.alternate_type_id",
                  },
                },
                select: { column: ["id", "name", "title"] },
              },
            }).query();

            response.data.forEach((column) => {
              object.participantModel.addColumn(column.name + "_consent", {
                title: column.title + " Consent",
                type: "boolean",
              });
            });
          }

          init(this);
        };

        return {
          instance: function () {
            return new object(false);
          },
        };
      },
    ]);
  },
});
