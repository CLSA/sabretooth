const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_element_card } = await import(`${CENOZO_URL}/js/element/card.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_element_loading_box } = await import(`${CENOZO_URL}/js/element/loading_box.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_script_launcher } = await import(`${CENOZO_URL}/js/script_launcher.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const { CN_voip } = await import(`${CENOZO_URL}/js/voip.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/assignment.mjs`);

export class CN_model_assignment extends classes.CN_model_assignment {
  /**
   * Extend parent method
   */
  clone_columns() {
    const control_columns = {
      rank: { title: "Rank", column: "queue.rank", type: "rank", max_rank: 11 },
      queue: { title: "Queue", column: "queue.name" },
      qnaire: { title: "Questionnaire", column: "script.name" },
      page_progress: { title: "Page Progress", table_prefix: false },
      language: { title: "Language", column: "language.name" },
      uid: { title: "UID", column: "participant.uid" },
      global_note: { title: "Special Note", column: "participant.global_note", type: "text", limit: 20 },
      availability: { title: "Availability", column: "availability_type.name" },
      participant_id: { column: "participant.id", is_hidden: () => true },
    };

    if (CN_session.get("setting", "last_contacted")) {
      CN_common.insert_property(control_columns, "after", "availability", "last_contacted", {
        title: "Last Contacted",
        column: "interview_last_contacted.datetime",
        type: "datetime",
        help: "The last time an assignment for the current questionnaire ended with a contacted call status.",
      });
    }

    return "control" == CN_session.get_leaf_model().get_action_name() ?  control_columns : super.clone_columns();
  }

  /**
   * Extend parent method
   */
  clone_properties() {
    const properties = super.clone_properties();

    CN_common.insert_property(properties, "after", "participant", "qnaire", {
      meta: { table: "script", column: "name" },
      title: "Questionnaire",
      is_constant: () => true,
    });

    CN_common.insert_property(properties, "after", "site", "queue", {
      meta: { table: "queue", column: "title" },
      title: "Queue",
      is_constant: () => true,
    });

    return properties;
  }

  /**
   * Extend parent method
   */
  get_default_order() {
    return (
      "control" == CN_session.get_leaf_model().get_action_name() ?
      { column: "rank", desc: false } :
      super.get_default_order()
    );
  }
}

// A private class used by the control assignment action
class CN_element_script_control extends CN_element_card {
  #assignment = null;
  #withdrawn = false;
  #proxy = false;
  #qnaire_list = [];
  #script_list = [];
  #active_script_id = null;

  #active_script_form_input = {};
  #advance_btn_el;
  #launch_btn_el;
  #script_launcher;

  constructor(parent_el, config = {}) {
    super(parent_el, {
      ...{
        header: '<div class="d-flex"><div class="flex-grow-1" name="title">Script Launcher</div></div>',
        body: "",
        footer: "",
      },
      ...config,
    });

    this.#advance_btn_el = this.constructor.html(`
      <button
        type="button"
        name="advance"
        class="btn btn-light btn-outline-primary w-50"
      >Advance Questionnaire</button>
    `);
    this.#advance_btn_el.addEventListener("click", this.#advance.bind(this));

    this.#launch_btn_el = this.constructor.html(`
      <button
        type="button"
        name="launch"
        class="btn btn-primary w-50"
      >Launch Script</button>
    `);
    this.#launch_btn_el.addEventListener("click", this.#launch.bind(this));
  }

  // getters and setters
  set_assignment(assignment) {
    this.#assignment = assignment;
    this.#withdrawn = false;
    this.#proxy = false;
    this.#script_list = [];
  }
  get_script_launcher() { return this.#script_launcher; }
  get_active_script() { return this.#script_list.find(script => script.id == this.#active_script_id); }
  get_active_qnaire() { return this.#qnaire_list.find(qnaire => qnaire.script_id == this.#active_script_id); }
  get_previous_qnaire() {
    const previous_rank = this.get_active_qnaire().rank - 1;
    return this.#qnaire_list.find(qnaire => qnaire.rank == previous_rank);
  }

  /**
   * ADD DOCS
   */
  can_advance() {
    if (!this.#assignment) return false;
    const active_qnaire_id = this.#qnaire_list.findIndex(qnaire => qnaire.script_id == this.#active_script_id);
    const last_qnaire_id = this.#qnaire_list.length - 1;
    return this.#active_script_id == this.#assignment.script_id && active_qnaire_id < last_qnaire_id;
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const body_el = this.get_element().querySelector(".card-body");
    const footer_el = this.get_element().querySelector(".card-footer");
    footer_el.innerHTML = "";

    const proxy_interview = CN_session.get("setting", "proxy");
    if (this.#withdrawn || (this.#proxy != proxy_interview)) {
      body_el.querySelector("div[name=interface]").classList.add("d-none");
      body_el.querySelector("span[name=reason]").innerHTML = (
        this.#withdrawn ?
        "they have withdrawn from the study" :
        proxy_interview ?
        "they are not ready for the proxy system" :
        "a proxy is required for the interview"
      );
      body_el.querySelector("div[name=blocked]").classList.remove("d-none");
    } else {
      body_el.querySelector("div[name=blocked]").classList.add("d-none");
      body_el.querySelector("div[name=interface]").classList.remove("d-none");

      this.#active_script_form_input.update();
      this.#active_script_form_input.set_value(this.#active_script_id);
      const active_script = this.get_active_script();

      // define active script details
      const started_datetime_el = body_el.querySelector("div[name=started_datetime]");
      const finished_datetime_el = body_el.querySelector("div[name=finished_datetime]");
      const repeated_el = body_el.querySelector("div[name=repeated]");
      const description_el = body_el.querySelector("div[name=description]");

      started_datetime_el.innerHTML = "Loading...";
      finished_datetime_el.innerHTML = "Loading...";
      repeated_el.innerHTML = "Loading...";
      description_el.innerHTML = "Loading...";

      if (active_script && active_script.supporting) {
        started_datetime_el.parentElement.classList.add("d-none");
        finished_datetime_el.parentElement.classList.add("d-none");
        repeated_el.parentElement.classList.add("d-none");
      } else {
        started_datetime_el.parentElement.classList.remove("d-none");
        finished_datetime_el.parentElement.classList.remove("d-none");
        repeated_el.parentElement.classList.remove("d-none");
      }

      if (active_script) {
        if (!active_script.supporting) {
          started_datetime_el.innerHTML = (
            active_script.started_datetime ?
            CN_common.format_datetime(active_script.started_datetime, "datetimesecond") :
            "(empty)"
          );
          finished_datetime_el.innerHTML = (
            active_script.finished_datetime ?
            CN_common.format_datetime(active_script.finished_datetime, "datetimesecond") :
            "(empty)"
          );
          repeated_el.innerHTML = active_script.repeated ? "Yes" : "No";
        }
        description_el.innerHTML = (
          null == active_script.description ?
          "(empty)" :
          CN_common.nl_to_br(active_script.description)
        );
      }

      // show the launch button, or both the advance and launch buttons (if the interview can be advanced only)
      let btn_group_el = null;
      if (this.can_advance()) {
        // the active script can be advanced
        btn_group_el = this.constructor.html('<div class="btn-group w-100" role="group"></div>');
        btn_group_el.append(this.#advance_btn_el);

        const disabled = null == this.get_active_qnaire().script.finished_datetime;
        this.constructor.set_disabled(this.#advance_btn_el, disabled);
        if (disabled) {
          this.#advance_btn_el.classList.remove("btn-outline-primary");
        } else {
          this.#advance_btn_el.classList.add("btn-outline-primary");
        }
      } else {
        // the active script cannot be advanced
        btn_group_el = this.constructor.html('<div class="d-flex flex-row-reverse w-100"></div>');
      }
      btn_group_el.append(this.#launch_btn_el);
      footer_el.append(btn_group_el);
    }
  }

  /**
   * ADD DOCS
   */
  async on_load() {
    if (!this.#assignment) return;

    const title_el = this.get_element().querySelector("div[name=title]");
    title_el.innerHTML = "Script Launcher (Loading...)";
    const [participant_response, qnaire_response, script_response] = await Promise.all([
      CN_api.get(`participant/${this.#assignment.participant_id}`, {
        select: { column: [
          { table: "hold_type", column: "name", alias: "hold" },
          { table: "proxy_type", column: "name", alias: "proxy" },
        ] },
      }),

      CN_api.get("qnaire", {
        select: { column: ["id", "rank", "script_id", "delay_offset", "delay_unit", "allow_missing_consent"] },
        modifier: { order: "rank" },
      }),

      CN_api.get("application/0/script", {
        participant_id: this.#assignment.participant_id,
        select: {
          column: [
            "id", "name", "repeated", "supporting", "url", "description",
            { table: "started_event", column: "datetime", alias: "started_datetime" },
            { table: "finished_event", column: "datetime", alias: "finished_datetime" },
          ],
        },
        modifier: { order: ["repeated", "name"] },
      }),
    ]);

    this.#withdrawn = "Withdrawn" == participant_response.hold;
    this.#proxy = null != participant_response.proxy;
    this.#qnaire_list = qnaire_response;
    this.#script_list = [];
    script_response.forEach(script => {
      const qnaire = this.#qnaire_list.find(qnaire => qnaire.script_id == script.id);
      if (qnaire) {
        qnaire.script = script;
        if (this.#assignment.qnaire_id == qnaire.id) this.#script_list.unshift(script);
      } else {
        this.#script_list.push(script);
      }
    });

    if (null == this.#active_script_id) {
      this.#active_script_id = 0 < this.#script_list.length ? this.#script_list[0].id : null;
    }

    title_el.innerHTML = "Script Launcher";
  }

  /**
   * Extend parent method
   */
  _create_element() {
    const el = super._create_element();
    const body_el = el.querySelector(".card-body");

    // add the refresh button to the header
    const refresh_btn_el = this.constructor.html(`
      <button type="button" name="refresh" class="btn btn-primary px-2 py-0">
        <i class="bi bi-arrow-clockwise fs-5"></i>
      </button>
    `);
    refresh_btn_el.addEventListener("click", this.on_load.bind(this));
    el.querySelector(".card-header").querySelector("div.d-flex").append(refresh_btn_el);
    new bootstrap.Tooltip(refresh_btn_el, {
      title: "Refresh Data",
      trigger: "hover",
      delay: { "show": 1000, "hide": 100 },
    });

    // add the script details to the interface
    const interface_el = this.constructor.html('<div name="interface"></div>');

    const props = [
      { name: "script", title: "Active Script" },
      { name: "started_datetime", title: "Started Date & Time" },
      { name: "finished_datetime", title: "Finished Date & Time" },
      { name: "repeated", title: "Repeated" },
      { name: "description", title: "Description" },
    ];

    props.forEach((prop, index) => {
      const row_el = this.constructor.html(`<div class="row ${0 == index ? "pb-1" : ""}"></div>`);
      CN_element_label.append(row_el, {
        for: prop.name,
        value: prop.title,
        class: "col-sm-3" + (0 == index ? "" : " pt-0 pb-1"),
      });
      if (0 == index) {
        this.#active_script_form_input = CN_input_enum.append(row_el, {
          id: "script",
          class: "col-sm-9",
          required: true,
          get_default: () => false, // setting to false so that "Choose an option" doesn't show
          enum: {
            get_enums: async () => this.#script_list.map(script => ({ key: script.id, value: script.name })),
          },
          on_change: (form_input) => {
            this.#active_script_id = form_input.get_value();
            this.update_element();
          },
        });
      } else {
        row_el.append(this.constructor.html(`<div name="${prop.name}" class="col-9">Loading...</div>`));
      }
      interface_el.append(row_el);
    });

    // add instructions to the interface
    interface_el.append(this.constructor.html(`
      <div class="alert alert-info mt-2 mb-0" role="alert">
        When launching on a script your browser will open the script in a new tab.
        The application will still be accessible by clicking the brower's
        <em>${CN_session.get("application", "title")}</em> tab.
        <br/>
        NOTE: If you already have the script tab open then selecting a new script will not automatically
        switch to the script tab.
        Simply select the script tab after you have clicked the <em>Launch Script</em> button.
      </div>
    `));

    body_el.append(interface_el);

    // add a notice for when the participant is withdrawn or not ready for a proxy interview
    body_el.append(this.constructor.html(`
      <div name="blocked" class="text-danger d-none">
        <i class="bi bi-exclamation-triangle-fill"></i>
        You may not launch any scripts for this participant since
        <span name="reason"></span>.
      </div>
    `));

    return el;
  }

  /**
   * ADD DOCS
   */
  async #advance() {
    await this.constructor.wait_for(CN_api.patch("assignment/0?operation=advance", {}));
    CN_session.reload()
  }

  /**
   * ADD DOCS
   */
  async #launch() {
    const active_script = this.get_active_script();
    const active_qnaire = this.get_active_qnaire();

    let do_not_proceed_reason = null;
    if (active_qnaire) {
      // if the application has a consent type then check if the script can proceed without consent
      const consent_type_id = CN_session.get("application", "consent_type_id");
      if (null != consent_type_id && !active_script.allow_missing_consent) {
        try {
          const response = await CN_api.get(
            `participant/${this.#assignment.participant_id}/consent/type=last;consent_type_id=${consent_type_id}`
          );

          if (!response.accept) {
            do_not_proceed_reason = `
              The participant cannot continue the interview as they
              have not consented to participate in the study.
            `;
          }
        } catch (error) {
          if (CN_common.is_uri_error(error, 404)) {
            do_not_proceed_reason =
              "The participant cannot continue the interview as they have declined to participate in the study.";
          } else {
            throw error;
          }
        }
      }

      // check that the qnaire isn't delayed
      if (null == do_not_proceed_reason && 0 < active_qnaire.delay_offset && 1 < active_qnaire.rank) {
        // test the delay until date with today (both at midnight) to see if the delay until date has been reached
        const delay_until = CN_common.add_date(
          CN_common.get_date(this.get_previous_qnaire().script.finished_datetime),
          active_qnaire.delay_unit,
          active_qnaire.delay_offset
        );
        delay_until.setHours(0);
        delay_until.setMinutes(0);
        delay_until.setSeconds(0);
        delay_until.setMilliseconds(0);

        const today = CN_common.get_date();
        today.setHours(0);
        today.setMinutes(0);
        today.setSeconds(0);
        today.setMilliseconds(0);

        if (delay_until > today) {
          do_not_proceed_reason = `
            The participant cannot continue to this script until
            ${CN_common.format_datetime(delay_until, "date", true)}.
            <br/>
            <br/>
            Please end your assignment now, the participant will become available for assignment after the
            delay has ended.
          `;
        }
      }
    }

    if (do_not_proceed_reason) {
      await CN_modal_message.create_and_open({
        title: "Interview Cannot Proceed",
        message: do_not_proceed_reason
      });
    } else {
      this.#script_launcher = new CN_script_launcher({
        script: active_script,
        identifier: this.#assignment.participant.id,
        lang: this.#assignment.participant.language_code,
      });
      await this.#script_launcher.initialize();

      const url_params = {
        show_hidden: 1,
        site: CN_session.get("site", "name"),
        username: CN_session.get("user", "name"),
      };
      if (this.#assignment.active_phone_call.alternate_id) {
        url_params.alternate_id = this.#assignment.active_phone_call.alternate_id;
      }
      await this.#script_launcher.open(url_params);
    }
  }
}

export class CN_control_assignment extends CN_action_list {
  #assignment = null;
  #phone_call_list = [];
  #active_phone_call = null;
  #previous_assignment = null;
  #update_assignment_duration_id = null;
  #phone_list = [];

  #regained_focus_fn;
  #no_assignment_body_el;
  #no_assignment_footer_el;
  #assignment_body_el;
  #assignment_footer_el;
  #script_control_el;

  constructor(parent_el, model) {
    super(parent_el, model, "control");

    this.#regained_focus_fn = async () => {
      await CN_common.sleep(100); // this helps to prevent an error when re-focus is gained by reloading the page
      await Promise.all([
        this.#script_control_el.on_load(),
        this.#update_page_progress(),
      ]);
      this.update_element();
    };
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("crumb" == type) {
      return (
        null == this.#assignment || null == this.#assignment.participant ?
        "Assignment Select" :
        `Assignment: ${this.#assignment.participant.uid}`
      );
    }

    if ("header" == type) {
      return (
        null == this.#assignment || null == this.#assignment.participant ?
        "Participant Selection List" :
        "Current Assignment"
      );
    }

    return await super.get_text(type);
  }

  /**
   * Replace parent method
   */
  get_on_load_path() {
    return "participant";
  }

  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    const params = super.get_on_load_parameters();
    params.assignment = true;
    return params;
  }

  /**
   * Extend parent method
   */
  async on_load() {
    this.#assignment = null;
    this.#phone_call_list = [];
    this.#previous_assignment = null;
    this.#update_assignment_duration_id = null;
    this.#phone_list = [];
    this.#script_control_el.set_assignment(null);

    try {
      // get current assignment details
      const assignment_column = [
        "id",
        "interview_id",
        "start_datetime",
        "has_alternate_types",
        { table: "participant", column: "id", alias: "participant_id" },
        { table: "qnaire", column: "id", alias: "qnaire_id" },
        { table: "qnaire", column: "web_version", type: "boolean" },
        { table: "script", column: "id", alias: "script_id" },
        { table: "script", column: "name", alias: "qnaire" },
        { table: "queue", column: "title", alias: "queue" },
        { table: "interview", column: "method", alias: "interview_method" },
      ];
      if (CN_session.get("application", "check_for_missing_hin")) assignment_column.push("missing_hin");
      if (CN_session.get("setting", "proxy")) assignment_column.push("use_decision_maker");
      this.#assignment = await CN_api.get("assignment/0", { select: { column: assignment_column } });
      this.#assignment.participant = null;
      this.#assignment.active_phone_call = null;

      // Show a popup if the participant is missing HIN data
      // Note: this will only show if the participant has consented to provide HIN
      if (CN_session.get("application", "check_for_missing_hin") && this.#assignment.missing_hin) {
        const modal = new CN_modal_message({
          title: "Missing HIN",
          message: `
            The participant has consented to provide their Health Insurance Number (HIN)
            but their number is not on file.
            <br/>
            <br/>
            Please ask the participant to provide their HIN number.
            The details can be added in the participant's file under "HIN" in the list selector or by
            <a href="#">clicking here</a>.
          `,
        });
        modal.get_element().querySelector("a").addEventListener("click", () => {
          modal.close();
          CN_session.navigate_to(`participant/view/${this.#assignment.participant_id}/hin/add`);
        });

        // Open but do not await, that way the rest of the assignment's details can finish loading
        // while the warning is being read.
        modal.open();
      }


      // get current participant details
      const participant_column = [
        "id",
        "uid",
        "honorific",
        "first_name",
        "other_name",
        "last_name",
        "gender_identity",
        "pronouns",
        "global_note",
        { table: "language", column: "code", alias: "language_code" },
        { table: "language", column: "name", alias: "language" },
      ];
      if (CN_session.get("application", "identifier")) {
        participant_column.push({ table: "participant_identifier", column: "value", alias: "study_id" });
      }

      const [
        participant_response,
        progress_response,
        phone_call_response,
        previous_assignment_response,
        phone_response,
      ] = await Promise.all([
        CN_api.get(`participant/${this.#assignment.participant_id}`, {
          select: { column: participant_column },
        }),

        this.#update_page_progress(),

        CN_api.get("assignment/0/phone_call", {
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
        }),

        CN_api.get(`interview/${this.#assignment.interview_id}/assignment`, {
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
        }),

        CN_api.get(`participant/${this.#assignment.participant_id}/phone`, {
          include_alternates: CN_session.get("setting", "proxy") || this.#assignment.has_alternate_types,
          select: { column: ["id", "rank", "type", "number", "international", "note"] },
          modifier: {
            where: { column: "phone.active", operator: "=", value: true },
            order: "rank",
          },
        }),
      ]);

      this.#assignment.participant = participant_response;
      this.#phone_call_list = phone_call_response;
      const len = this.#phone_call_list.length
      this.#assignment.active_phone_call = (
        0 < len && null == this.#phone_call_list[len - 1].end_datetime ?
        this.#phone_call_list[len - 1] :
        null
      );
      this.#previous_assignment = (
        1 == previous_assignment_response.length ?
        previous_assignment_response[0] :
        null
      );

      let last_person = null;
      this.#phone_list = phone_response.map(phone => {
        phone.new_person = phone.person != last_person;
        last_person = phone.person;
        return phone;
      });

      this.#script_control_el.set_assignment(this.#assignment);
      if (this.#assignment.active_phone_call) {
        await this.#script_control_el.on_load();

        // re-run the action once the user returns to this tab
        window.addEventListener("focus", this.#regained_focus_fn);
      }
    } catch (error) {
      this.#assignment = null;

      if (CN_common.is_uri_error(error, 307)) {
        // 307 means the user has no active assignment, so load the participant select list
        await super.on_load();
      } else if (CN_common.is_uri_error(error, 403)) {
        // 403 means the user has an assignment but is logged in under the wrong site
      } else {
        throw error;
      }
    }
  }

  /**
   * Extend parent method
   */
  async on_row_click(record) {
    // attempt to assign the participant to the user
    const response = await CN_modal_confirm.create_and_open({
      title: "Begin Assignment",
      message: `
        Are you sure you wish to start a new assignment with participant
        <span class="fw-bold">${record.uid}</span>?
      `,
    });

    if (response) this.#start_assignment(record.participant_id);
  }

  /**
   * Extend parent method
   */
  update_element() {
    const header_el = this.get_header_element();
    (async () => { header_el.querySelector("div.flex-grow-1").innerHTML = await this.get_text("header"); })();
    if (!this.#no_assignment_body_el) return;

    if (null == this.#assignment) {
      this.#no_assignment_body_el.classList.remove("d-none");
      this.#assignment_body_el.classList.add("d-none");
      this.#no_assignment_footer_el.classList.remove("d-none");
      this.#assignment_footer_el.classList.add("d-none");
      this.#script_control_el.get_element().classList.add("d-none");
      super.update_element();
    } else {
      const proxy = CN_session.get("setting", "proxy");

      // fill in the details properties
      const details_el = this.#assignment_body_el.querySelector("div[name=details]");
      details_el.querySelector("div[name=uid]").innerHTML = this.#assignment.participant.uid;
      if (CN_session.get("application", "identifier")) {
        details_el.querySelector("div[name=study]").innerHTML = this.#assignment.participant.study_id;
      }
      details_el.querySelector("div[name=participant]").innerHTML = [
        this.#assignment.participant.honorific,
        this.#assignment.participant.first_name,
        this.#assignment.participant.other_name ? "(" + this.#assignment.participant.other_name + ")" : null,
        this.#assignment.participant.last_name
      ].join(" ");
      if (proxy) {
        details_el.querySelector("div[name=dm]").innerHTML = this.#assignment.use_decision_maker ? "Yes" : "No";
      }
      details_el.querySelector("div[name=language]").innerHTML = this.#assignment.participant.language;
      details_el.querySelector("div[name=gender]").innerHTML = this.#assignment.participant.gender_identity;
      details_el.querySelector("div[name=pronouns]").innerHTML = (
        !this.#assignment.participant.pronouns ?
        "(empty)" :
        this.#assignment.participant.pronouns
      );
        this.#assignment.participant.pronouns;
      details_el.querySelector("div[name=queue]").innerHTML = this.#assignment.queue;
      details_el.querySelector("div[name=qnaire]").innerHTML = this.#assignment.qnaire;
      details_el.querySelector("div[name=page]").innerHTML = this.#assignment.page_progress;
      details_el.querySelector("div[name=note]").innerHTML = (
        null == this.#assignment.participant.global_note ?
        "(empty)" :
        CN_common.nl_to_br(this.#assignment.participant.global_note)
      );

      // We don't know whether to use the method property until after the assignment has been loaded,
      // so it has to be shown or hidden here.
      const method_el = details_el.querySelector("div[name=method]");
      if (this.#assignment.web_version) {
        method_el.parentElement.classList.remove("d-none");
        details_el.querySelector("div[name=method]").innerHTML = this.#assignment.interview_method;
      } else {
        method_el.parentElement.classList.add("d-none");
      }

      // fill in the active assignment properties
      const active_el = this.#assignment_body_el.querySelector("div[name=active-assignment]");
      active_el.querySelector("div[name=start]").innerHTML =
        CN_common.format_datetime(this.#assignment.start_datetime, "datetimesecond", true);
      active_el.querySelector("div[name=calls]").innerHTML = this.#phone_call_list.length;

      // start tracking the assignment duration
      if (null == this.#update_assignment_duration_id) {
        this.#update_assignment_duration();
        this.#update_assignment_duration_id = setInterval(() => { this.#update_assignment_duration() }, 1000);
      }

      if (null == this.#assignment.active_phone_call) {
        active_el.querySelector("div[name=call]").innerHTML = "No active call";
      } else {
        active_el.querySelector("div[name=call]").innerHTML = `
          ${this.#assignment.active_phone_call.person}<br/>
          ${this.#assignment.active_phone_call.rank}. ${this.#assignment.active_phone_call.type} (${this.#assignment.active_phone_call.number})
        `;
      }

      // fill in the previous assignment properties
      const previous_el = this.#assignment_body_el.querySelector("div[name=previous-assignment]");
      if (null == this.#previous_assignment) {
        previous_el.innerHTML = `
          This participant has never been called for the "${this.#assignment.qnaire}" questionnaire.
        `;
      } else {
        previous_el.querySelector("div[name=user]").innerHTML = [
          this.#previous_assignment.first_name,
          this.#previous_assignment.last_name,
          `(${this.#previous_assignment.name})`
        ].join(" ");
        previous_el.querySelector("div[name=start]").innerHTML =
          CN_common.format_datetime(this.#previous_assignment.start_datetime, "datetimesecond", true);
        previous_el.querySelector("div[name=end]").innerHTML =
          CN_common.format_datetime(this.#previous_assignment.end_datetime, "datetimesecond", true);
        previous_el.querySelector("div[name=calls]").innerHTML = this.#previous_assignment.phone_call_count;
        previous_el.querySelector("div[name=status]").innerHTML = this.#previous_assignment.status;
      }

      const end_assignment_el = this.#assignment_footer_el.querySelector("button[name=end-assignment]");
      const call_el = this.#assignment_footer_el.querySelector("button[name=call]");
      const call_list_el = this.#assignment_footer_el.querySelector("ul[name=call-list]");
      const use_tz_el = this.#assignment_footer_el.querySelector("button[name=use-tz]");
      const use_tz_list_el = this.#assignment_footer_el.querySelector("ul[name=use-tz-list]");

      call_el.innerHTML = this.#assignment.active_phone_call ? "End Call" : "Call";
      this.constructor.set_disabled(end_assignment_el, null != this.#assignment.active_phone_call);
      if (0 == this.#phone_list.length) {
        if (proxy) {
          use_tz_el.classList.remove("btn-outline-primary");
          this.constructor.set_disabled(use_tz_el, true);
        }
        if (null == this.#assignment.active_phone_call) this.constructor.set_disabled(call_el, true);
      } else {
        if (proxy) {
          use_tz_el.classList.add("btn-outline-primary");
          this.constructor.set_disabled(use_tz_el, false);
        }
        this.constructor.set_disabled(call_el, false);

        // when in proxy mode populate the use timezone dropdown with each alternate and the participant
        if (proxy) {
          use_tz_list_el.innerHTML = "";
          this.#phone_list.filter(phone => phone.new_person).forEach(phone => {
            const li_el = this.constructor.html(`
              <li><button type="button" class="dropdown-item">${phone.person_name}</button></li>
            `);
            li_el.querySelector("button").addEventListener("click", () => {
              const data = {};
              if (phone.alternate_id) {
                data.alternate_id = phone.alternate_id;
              } else {
                data.participant_id = this.#assignment.participant_id;
              }
              CN_session.set_timezone(data, CN_session.get("user", "am_pm"));
            });
            use_tz_list_el.append(li_el);
          });
        }

        // populate the call dropdown with phone-call statuses if in an active call, or list of numbers if not
        call_list_el.innerHTML = "";
        if (this.#assignment.active_phone_call) {
          CN_session.get_module("phone_call").get_property("status").enum_list.forEach(status => {
            const li_el = this.constructor.html(`
              <li><button type="button" class="dropdown-item">${status}</button></li>
            `);
            li_el.querySelector("button").addEventListener("click", this.#end_call.bind(this, status));
            call_list_el.append(li_el);
          });
        } else {
          this.#phone_list.forEach((phone, index) => {
            const li_el = this.constructor.html("<li></li>");
            if (phone.new_person) {
              li_el.innerHTML = `
                ${0 < index ? '<hr class="m-0" />' : ""}
                <div class="fw-bold px-2 py-1">${phone.person}</div>
                <hr class="m-0" />
              `;
            }
            const button_el = this.constructor.html(`
              <button type="button" class="dropdown-item d-flex">
                <div>${phone.rank}. ${phone.type}</div>
                <div class="w-100 text-end">${phone.number}</div>
              </button>
            `);
            if (phone.note) {
              button_el.setAttribute("data-bs-toggle", "tooltip");
              button_el.setAttribute("data-bs-placement", "right");
              button_el.setAttribute("data-bs-html", "true");
              button_el.setAttribute("data-bs-title", CN_common.nl_to_br(phone.note.replace(/"/g, "&quot;")));
              new bootstrap.Tooltip(button_el);
            }
            button_el.addEventListener("click", this.#start_call.bind(this, phone));
            li_el.append(button_el);
            call_list_el.append(li_el);
          });
        }
      }

      this.#assignment_body_el.classList.remove("d-none");
      this.#no_assignment_body_el.classList.add("d-none");
      this.#assignment_footer_el.classList.remove("d-none");
      this.#no_assignment_footer_el.classList.add("d-none");

      if (this.#assignment.active_phone_call) {
        this.#script_control_el.update_element();
        this.#script_control_el.get_element().classList.remove("d-none");
      } else {
        this.#script_control_el.get_element().classList.add("d-none");
      }
    }
  }

  /**
   *  Replace parent method
   */
  hide_placeholder() {
    super.hide_placeholder();

    const model_el = this.get_model().get_element();

    // also add the assignment body and footer to the element's card body
    const card_body_el = model_el.querySelector(":scope > div > div.card > .card-body");
    card_body_el.append(this.#assignment_body_el);

    const card_footer_el = model_el.querySelector(":scope > div > div.card > .card-footer");
    card_footer_el.append(this.#assignment_footer_el);
  }


  /**
   *  Replace parent method
   */
  show_placeholder() {
    super.show_placeholder();

    // hide the script launcher while loading
    this.#script_control_el.get_element().classList.add("d-none");
  }

  /**
   * Extend parent method
   */
  async on_dom_add() {
    await super.on_dom_add();
    window.addEventListener("unload", () => {
      // make sure that the script has been closed
      const script_launcher = this.#script_control_el.get_script_launcher();
      if (script_launcher) script_launcher.close();
    });
  }

  /**
   * Extend parent method
   */
  async on_dom_remove() {
    await super.on_dom_remove();

    // stop tracking the assignment duration
    if (this.#update_assignment_duration_id) {
      clearInterval(this.#update_assignment_duration_id);
      this.#update_assignment_duration_id = null
    }

    // remove the window focus event listener
    window.removeEventListener("focus", this.#regained_focus_fn);
  }

  /**
   *  Replace parent method
   */
  _create_placeholder_element() {
    return CN_element_loading_box.create();
  }

  /**
   * Extend parent method
   */
  _create_header_element() {
    const header_el = super._create_header_element();
    // remove the report button, we don't need it
    header_el.querySelector("div[name=report]").remove();
    return header_el;
  }

  /**
   * Extend parent method
   */
  _create_body_element() {
    this.#no_assignment_body_el = super._create_body_element();
    this.#no_assignment_body_el.classList.add("d-none");

    this.#assignment_body_el = this.constructor.html(`
      <div>
        <div name="details" class="p-3 pb-0"></div>
        <hr/>
        <div class="row p-3">
          <div class="col-md-6">
            <h4 class="text-center">Active Assignment</h4>
            <div class="card bg-body-secondary" style="min-height: 11em;">
              <div name="active-assignment" class="card-body"></div>
            </div>
          </div>
          <div class="col-md-6">
            <h4 class="text-center">Previous Assignment</h4>
            <div class="card bg-body-secondary" style="min-height: 11em;">
              <div name="previous-assignment" class="card-body"></div>
            </div>
          </div>
        </div>
      </div>
    `);

    // add the details props
    const details_el = this.#assignment_body_el.querySelector("div[name=details]");

    const details_props = [
      { name: "uid", title: "UID" },
      { name: "participant", title: "Participant" },
      { name: "language", title: "Preferred Language" },
      { name: "gender", title: "Gender Identity" },
      { name: "pronouns", title: "Pronouns" },
      { name: "queue", title: "Referring Queue" },
      { name: "qnaire", title: "Questionnaire" },
      { name: "page", title: "Page Progress" },
      { name: "note", title: "Special Notes" },
      { name: "method", title: "Interviewing Method" },
    ];

    // add optional props
    if (CN_session.get("application", "identifier")) {
      details_props.splice(1, 0, { name: "study", title: "Study ID" });
    }
    if (CN_session.get("setting", "proxy")) {
      details_props.splice(
        details_props.findIndex(o => "language" == o.name),
        0,
        { name: "dm", title: "Use Decision Maker" }
      );
    }

    details_props.forEach(prop => {
      const row_el = this.constructor.html('<div class="row"></div>');
      CN_element_label.append(row_el, { class: "col-4 pt-0 pb-1", value: prop.title });
      row_el.append(this.constructor.html(`<div name="${prop.name}" class="col-8"></div>`));
      details_el.append(row_el);
    });

    // add the active assignment props
    const active_assignment_el = this.#assignment_body_el.querySelector("div[name=active-assignment]");

    const active_assignment_props = [
      { name: "start", title: "Start Date & Time" },
      { name: "calls", title: "Number of Calls" },
      { name: "duration", title: "Assignment Time" },
      { name: "call", title: "Connected To" },
    ];

    active_assignment_props.forEach(prop => {
      const row_el = this.constructor.html('<div class="row"></div>');
      CN_element_label.append(row_el, { class: "col-4 pt-0 pb-1", value: prop.title });
      row_el.append(this.constructor.html(`<div name="${prop.name}" class="col-8"></div>`));
      active_assignment_el.append(row_el);
    });

    // add the previous assignment props
    const previous_assignment_el = this.#assignment_body_el.querySelector("div[name=previous-assignment]");

    const previous_assignment_props = [
      { name: "user", title: "Interviewer" },
      { name: "start", title: "Start Date & Time" },
      { name: "end", title: "End Date & Time" },
      { name: "calls", title: "Number of Calls" },
      { name: "status", title: "Last Phone Status" },
    ];

    previous_assignment_props.forEach(prop => {
      const row_el = this.constructor.html('<div class="row"></div>');
      CN_element_label.append(row_el, { class: "col-4 pt-0 pb-1", value: prop.title });
      row_el.append(this.constructor.html(`<div name="${prop.name}" class="col-8"></div>`));
      previous_assignment_el.append(row_el);
    });

    return this.#no_assignment_body_el;
  }

  /**
   * Extend parent method
   */
  _create_footer_element() {
    this.#no_assignment_footer_el = super._create_footer_element();
    this.#no_assignment_footer_el.classList.add("d-none");

    this.#assignment_footer_el = this.constructor.html(`
      <div>
        <div name="navigation" class="btn-group d-flex w-100 pb-1" role="group">
          <button
            type="button"
            class="btn btn-light btn-outline-primary"
            name="view-participant"
          >View Participant</button>
          <button
            type="button"
            class="btn btn-light btn-outline-primary"
            name="view-interview"
          >View Interview</button>
          <button
            type="button"
            class="btn btn-light btn-outline-primary"
            name="notes"
          >Notes</button>
          <button
            type="button"
            class="btn btn-light btn-outline-primary"
            name="history"
          >History</button>
        </div>
        <div class="btn-group w-100 pt-1" role="group">
          <div class="btn-group w-50" role="group">
            <button
              type="button"
              name="call"
              class="btn btn-primary dropdown-toggle"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            ></button>
            <ul name="call-list" class="dropdown-menu w-100"></ul>
          </div>
          <button type="button" class="btn btn-success w-50" name="end-assignment">End Assignment</button>
        </div>
      </div>
    `);

    this.#assignment_footer_el.querySelector("button[name=view-participant]").addEventListener("click", () => {
      CN_session.navigate_to(`participant/view/${this.#assignment.participant_id}`);
    });
    this.#assignment_footer_el.querySelector("button[name=view-interview]").addEventListener("click", () => {
      CN_session.navigate_to(`interview/view/${this.#assignment.interview_id}`);
    });
    this.#assignment_footer_el.querySelector("button[name=notes]").addEventListener("click", () => {
      CN_session.navigate_to(`participant/notes/${this.#assignment.participant_id}`);
    });
    this.#assignment_footer_el.querySelector("button[name=history]").addEventListener("click", () => {
      CN_session.navigate_to(`participant/history/${this.#assignment.participant_id}`);
    });
    this.#assignment_footer_el.querySelector("button[name=end-assignment]").addEventListener(
      "click",
      this.#end_assignment.bind(this)
    );

    const navigation_el = this.#assignment_footer_el.querySelector("div[name=navigation]");
    if (CN_session.get("setting", "proxy")) {
      navigation_el.append(this.constructor.html(`
        <div class="btn-group flex-fill" role="group">
          <button
            type="button"
            name="use-tz"
            class="btn btn-light btn-outline-primary dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >Use Timezone</button>
          <ul name="use-tz-list" class="dropdown-menu w-100">
          </ul>
        </div>
      `));
    } else {
      const use_tz_btn_el = this.constructor.html(`
        <button type="button" class="btn btn-light btn-outline-primary" name="use-tz">Use Timezone</button>
      `);
      use_tz_btn_el.addEventListener("click", () => {
        CN_session.set_timezone(
          { participant_id: this.#assignment.participant_id },
          CN_session.get("user", "am_pm")
        );
      });
      navigation_el.append(use_tz_btn_el);
    }

    return this.#no_assignment_footer_el;
  }

  /**
   * Extend parent method
   */
  _create_element() {
    const el = super._create_element();
    this.#script_control_el = CN_element_script_control.append(el, { class: "d-none" });
    return el;
  }

  /**
   * ADD DOCS
   */
  async #update_page_progress() {
    if (!this.#assignment) return;

    try {
      const response = await CN_api.get("assignment/0", {
        update_data: 1,
        select: { column: "page_progress" },
      });
      this.#assignment.page_progress = response.page_progress;
    } catch (error) {
      if (CN_common.is_uri_error(error, 307)) {
        // 307 means the user has no active assignment, so just refresh the page data
        await this.run();
      } else {
        throw error;
      }
    }
  }

  /**
   * ADD DOCS
   */
  async #update_assignment_duration() {
    let duration = "(loading...)"
    if (this.#assignment) {
      const duration_parts = [];
      let seconds = Math.floor((CN_common.get_date() - new Date(this.#assignment.start_datetime))/1000);
      if (86400 <= seconds) {
        const days = Math.floor(seconds / 86400);
        seconds -= 86400 * days;
        duration_parts.push(`${days} day${1 == days ? "" : "s"}`);
      }
      if (3600 <= seconds) {
        const hours = Math.floor(seconds / 3600);
        seconds -= 3600 * hours;
        duration_parts.push(`${hours} hour${1 == hours ? "" : "s"}`);
      }
      if (60 <= seconds) {
        const minutes = Math.floor(seconds / 60);
        seconds -= 60 * minutes;
        duration_parts.push(`${minutes} minute${1 == minutes ? "" : "s"}`);
      }
      duration_parts.push(`${seconds} second${1 == seconds ? "" : "s"}`);
      duration = duration_parts.join(", ");
    }

    const active_el = this.#assignment_body_el.querySelector("div[name=active-assignment]");
    active_el.querySelector("div[name=duration]").innerHTML = duration;
  }

  /**
   * ADD DOCS
   */
  async #start_call(phone) {
    // start a call
    await CN_voip.update();

    var proceed = false;
    if (
      !CN_voip.get_enabled() || (
        CN_common.is_object(CN_voip.get_info()) &&
        "UNKNOWN" == CN_voip.get_info().status &&
        CN_session.get("setting", "call_without_webphone")
      )
    ) {
      proceed = true;
    } else {
      if (!CN_voip.get_info()) {
        if (!CN_session.get("setting", "call_without_webphone")) {
          await CN_modal_message.create_and_open({
            header_class: "text-bg-danger",
            title: "Webphone Not Found",
            message: `
              You cannot start a call without a webphone connection.
              <br/>
              To use the built-in telephone system click on the "Webphone" link under the
              "Utilities" submenu and make sure the webphone client is connected.
            `,
          });
        } else {
          proceed = await CN_modal_confirm.create_and_open({
            title: "Webphone Not Found",
            message: `
              You are about to place a call with no webphone connection.
              If you choose to proceed you will have to contact the participant without the use
              of the software-based telephone system.
              If you wish to use the built-in telephone system click "No", then click on the
              "Webphone" link under the "Utilities" submenu to connect to the webphone.
              <br/>
              <br/>
              Do you wish to proceed without a webphone connection?
            `,
          });
        }
      } else {
        if (phone.international) {
          proceed = await CN_modal_confirm.create_and_open({
            title: "International Phone Number",
            message: `
              The phone number you are about to call is international.
              The VoIP system cannot place international calls so if you choose to proceed you
              will have to contact the participant without the use of the software-based
              telephone system.
              <br/>
              <br/>
              Do you wish to proceed without a webphone connection?
            `,
          });
        } else {
          const response = await CN_api.post("voip", { phone_id: phone.id });
          if (201 == response.status) {
            proceed = true;
          } else {
            proceed = await CN_modal_confirm.create_and_open({
              header_class: "text-bg-danger",
              title: "Webphone Error",
              message: `
                The telephone system was unable to find the call which was just placed.
                If you are connected to a call please select "Proceed with call" to proceed
                with the interview, otherwise please click the "Cancel" button and you will
                be able to try the call again
              `,
              no_text: "Cancel",
              yes_text: "Proceed with call",
            });
          }
        }
      }
    }

    if (proceed) {
      await CN_api.post("phone_call?operation=open", { phone_id: phone.id });
      await this.run();
    }
  }

  /**
   * ADD DOCS
   */
  async #end_call(status) {
    if (CN_voip.get_enabled() && CN_voip.get_info() && !this.#assignment.active_phone_call.international) {
      try {
        await CN_api.delete("voip/0");
      } catch (error) {
        if (CN_common.is_uri_error(error, 404)) {
          // ignore 404 errors, it just means there was no phone call found to hang up
        } else {
          throw error;
        }
      }
    }

    await CN_api.patch("phone_call/0?operation=close", { status: status });
    await this.run();
  }

  /**
   * ADD DOCS
   */
  async #start_assignment(participant_id) {
    try {
      await this.constructor.wait_for(
        CN_api.post("assignment?operation=open", { participant_id: participant_id }),
        0,
      );
      CN_session.reload();
    } catch (error) {
      if (CN_common.is_uri_error(error, 409)) {
        // 409 means there are no participants or a conflict (the assignment can't be made)
        await CN_modal_message.create_and_open({
          header_class: "text-bg-danger",
          title: "Unable to start assignment",
          message: JSON.parse(error.body),
        });
      } else {
        throw error;
      }
    }
  }

  /**
   * ADD DOCS
   */
  async #end_assignment() {
    if (this.#assignment) {
      await this.constructor.wait_for(async () => {
        try {
          // check that there's an active assignment
          await CN_api.get("assignment/0");

          // make sure that the script has been closed
          const script_launcher = this.#script_control_el.get_script_launcher();
          if (script_launcher) script_launcher.close();
          await CN_api.patch("assignment/0?operation=close", {});
        } catch (error) {
          // 307 means the user's assignment has already been closed, so we can ignore it
          if (!CN_common.is_uri_error(error, 307)) throw error;
        }
      }, 0);
      CN_session.reload();
    }
  }
}
