const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_element_loading_box } = await import(`${CENOZO_URL}/js/element/loading_box.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
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

export class CN_control_assignment extends CN_action_list {
  #assignment = null;
  #participant = null;
  #phone_call_list = [];
  #previous_assignment = null;
  #update_assignment_duration_id = null;
  #phone_list = [];

  #no_assignment_body_el;
  #no_assignment_footer_el;
  #assignment_body_el;
  #assignment_footer_el;

  constructor(parent_el, model) {
    super(parent_el, model, "control");
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    await this.after_first_load();

    if ("crumb" == type) {
      return null == this.#participant ?  "Assignment Select" : `Assignment: ${this.#participant.uid}`;
    }

    if ("header" == type) {
      return null == this.#participant ?  "Participant Selection List" : "Current Assignment";
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

      this.#participant = participant_response;
      this.#phone_call_list = phone_call_response;
      this.#previous_assignment = (
        1 == previous_assignment_response.length ?
        previous_assignment_response[0] :
        null
      );
      this.#phone_list = phone_response;
    } catch (error) {
      this.#assignment = null;
      this.#participant = null;

      if (307 == error.response.status) {
        // 307 means the user has no active assignment, so load the participant select list
        await super.on_load();
      } else if (403 == error.status) {
        // 403 means the user has an assignment but is logged in under the wrong site
      } else {
        throw error;
      }
    }

    // TODO: need to update UI to show we're in an assignment (v2: CnSession.updateData())
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

    if (response) {
      try {
        await this.constructor.wait_for(async () => {
          await CN_api.post("assignment?operation=open", { participant_id: record.participant_id });
        }, 0);
        await this.run();
      } catch (error) {
        if (409 == error.response.status) {
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
      super.update_element();
    } else {
      // fill in the details properties
      const details_el = this.#assignment_body_el.querySelector("div[name=details]");
      details_el.querySelector("div[name=uid]").innerHTML = this.#participant.uid;
      if (CN_session.get("application", "identifier")) {
        details_el.querySelector("div[name=study]").innerHTML = this.#participant.study_id;
      }
      details_el.querySelector("div[name=participant]").innerHTML = [
        this.#participant.honorific,
        this.#participant.first_name,
        this.#participant.other_name ? "(" + this.#participant.other_name + ")" : null,
        this.#participant.last_name
      ].join(" ");
      if (CN_session.get("setting", "proxy")) {
        details_el.querySelector("div[name=dm]").innerHTML = this.#assignment.use_decision_maker ? "Yes" : "No";
      }
      details_el.querySelector("div[name=language]").innerHTML = this.#participant.language;
      details_el.querySelector("div[name=gender]").innerHTML = this.#participant.gender_identity;
      details_el.querySelector("div[name=pronouns]").innerHTML = (
        !this.#participant.pronouns ?
        "(empty)" :
        this.#participant.pronouns
      );
        this.#participant.pronouns;
      details_el.querySelector("div[name=queue]").innerHTML = this.#assignment.queue;
      details_el.querySelector("div[name=qnaire]").innerHTML = this.#assignment.qnaire;
      details_el.querySelector("div[name=page]").innerHTML = this.#assignment.page_progress;
      details_el.querySelector("div[name=note]").innerHTML = (
        null == this.#participant.global_note ?
        "(empty)" :
        CN_common.nl_to_br(this.#participant.global_note)
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

      if (0 == this.#phone_call_list.length) {
        active_el.querySelector("div[name=call]").innerHTML = "No active call";
      } else {
        const active_call = this.#phone_call_list[this.#phone_call_list.length-1];
        active_el.querySelector("div[name=call]").innerHTML = `
          ${active_call.person}<br/>
          ${active_call.rank}. ${active_call.type} (${active_call.number})
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

      if (CN_session.get("setting", "proxy")) {
        const use_timezone_list_el = this.#assignment_footer_el.querySelector("ul[name=use-timezone-list]");
        if (0 == this.#phone_list.length) {
          //this.constructor.set_disabled(use_timezone_el, true);
        } else {
          //this.constructor.set_disabled(use_timezone_el, false);
          this.#phone_list.forEach(phone => {
            const li_el = this.constructor.html(`
              <li>
                <button type="button" class="dropdown-item">${phone.person_name}</button>
              </li>
            `);
            li_el.querySelector("button").addEventListener("click", () => {
              const data = {};
              if (phone.alternate_id) {
                data.alternate_id = phone.alternate_id;
              } else {
                data.participant_id = this.#assignment.participant_id;
              }
              CN_session.set_timezone(data, CN_session.get("user", "am_pm"));
            })
            use_timezone_list_el.append(li_el);
          });
        }
      }

      this.#assignment_body_el.classList.remove("d-none");
      this.#no_assignment_body_el.classList.add("d-none");
      this.#assignment_footer_el.classList.remove("d-none");
      this.#no_assignment_footer_el.classList.add("d-none");
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
   * Extend parent method
   */
  async on_dom_add() {
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
        details_props.findIndex(o => "participant" == o.name),
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
          <button
            type="button"
            class="btn btn-primary"
            name="call"
          >Call</button>
          <button
            type="button"
            class="btn btn-success"
            name="end-assignment"
          >End Assignment</button>
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

    const navigation_el = this.#assignment_footer_el.querySelector("div[name=navigation]");
    if (CN_session.get("setting", "proxy")) {
      navigation_el.append(this.constructor.html(`
        <div class="btn-group flex-fill" role="group">
          <button
            type="button"
            name="use-timezone"
            class="btn btn-light btn-outline-primary dropdown-toggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >Use Timezone</button>
          <ul name="use-timezone-list" class="dropdown-menu">
          </ul>
        </div>
      `));
    } else {
      const use_timezone_btn_el = this.constructor.html(`
        <button type="button" class="btn btn-light btn-outline-primary" name="use-timezone">Use Timezone</button>
      `);
      use_timezone_btn_el.addEventListener("click", () => {
        CN_session.set_timezone(
          { participant_id: this.#assignment.participant_id },
          CN_session.get("user", "am_pm")
        );
      });
      navigation_el.append(use_timezone_btn_el);
    }

    return this.#no_assignment_footer_el;
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
      if (307 == error.response.status) {
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
    const duration_parts = [];
    let seconds = Math.floor((CN_common.get_date() - new Date(this.#assignment.start_datetime))/1000);
    if (86400 < seconds) {
      const days = Math.floor(seconds / 86400);
      seconds -= 86400 * days;
      duration_parts.push(`${days} day${1 == days ? "" : "s"}`);
    }
    if (3600 < seconds) {
      const hours = Math.floor(seconds / 3600);
      seconds -= 3600 * hours;
      duration_parts.push(`${hours} hour${1 == hours ? "" : "s"}`);
    }
    if (60 < seconds) {
      const minutes = Math.floor(seconds / 60);
      seconds -= 60 * minutes;
      duration_parts.push(`${minutes} minute${1 == minutes ? "" : "s"}`);
    }
    duration_parts.push(`${seconds} second${1 == seconds ? "" : "s"}`);

    const active_el = this.#assignment_body_el.querySelector("div[name=active-assignment]");
    active_el.querySelector("div[name=duration]").innerHTML = duration_parts.join(", ");
  }
}
