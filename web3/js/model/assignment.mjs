const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_element_loading_box } = await import(`${CENOZO_URL}/js/element/loading_box.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_script } = await import(`${CENOZO_URL}/js/script.mjs`);
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

export class CN_control_assignment extends CN_action_list {
  #assignment = null;
  #participant = null;
  #phone_call_list = [];
  #active_phone_call = null;
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
    this.#assignment = null;
    this.#participant = null;
    this.#phone_call_list = [];
    this.#active_phone_call = null;
    this.#previous_assignment = null;
    this.#update_assignment_duration_id = null;
    this.#phone_list = [];

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
      const len = this.#phone_call_list.length
      this.#active_phone_call = (
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
      super.update_element();
    } else {
      const proxy = CN_session.get("setting", "proxy");

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
      if (proxy) {
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

      if (null == this.#active_phone_call) {
        active_el.querySelector("div[name=call]").innerHTML = "No active call";
      } else {
        active_el.querySelector("div[name=call]").innerHTML = `
          ${this.#active_phone_call.person}<br/>
          ${this.#active_phone_call.rank}. ${this.#active_phone_call.type} (${this.#active_phone_call.number})
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

      call_el.innerHTML = this.#active_phone_call ? "End Call" : "Call";
      this.constructor.set_disabled(end_assignment_el, null != this.#active_phone_call);
      if (0 == this.#phone_list.length) {
        if (proxy) {
          use_tz_el.classList.remove("btn-outline-primary");
          this.constructor.set_disabled(use_tz_el, true);
        }
        if (null == this.#active_phone_call) this.constructor.set_disabled(call_el, true);
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
        if (this.#active_phone_call) {
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
          <div class="btn-group flex-fill" role="group">
            <button
              type="button"
              name="call"
              class="btn btn-primary dropdown-toggle"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            ></button>
            <ul name="call-list" class="dropdown-menu w-100">
            </ul>
          </div>
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
        angular.isObject(CN_voip.get_info()) &&
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
    if (CN_voip.get_enabled() && CN_voip.get_info() && !this.#active_phone_call.international) {
      try {
        await CN_api.delete("voip/0");
      } catch (error) {
        if (404 == error.response.status) {
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
      await this.constructor.wait_for(async () => {
        await CN_api.post("assignment?operation=open", { participant_id: participant_id });
      }, 0);
      CN_session.reload();
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

  /**
   * ADD DOCS
   */
  async #end_assignment() {
    if (this.#assignment) {
      await this.constructor.wait_for(async () => {
        try {
          // check that there's an active assignment
          await CN_api.get("assignment/0");

          // make absolute sure that the script has been closed
          CN_script.close();
          await CN_api.patch("assignment/0?operation=close", {});
        } catch (error) {
          // 307 means the user's assignment has already been closed, so we can ignore it
          if (307 != error.response.status) throw error;
        }
      }, 0);
      CN_session.reload();
    }
  }
}
