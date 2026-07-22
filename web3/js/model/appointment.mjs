const { CN_action_add } = await import(`${CENOZO_URL}/js/action/add.mjs`);
const { CN_action_calendar } = await import(`${CENOZO_URL}/js/action/calendar.mjs`);
const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_model_user } = await import(`${CENOZO_URL}/js/model/user.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_appointment extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "appointment",
        plural: "appointments",
        posessive: "appointment's",
      },
      columns: {
        uid: { column: "participant.uid", title: "UID" },
        start_datetime: { type: "datetime", title: "Date & Time", table_prefix: false, },
        duration: { title: "Duration", table_prefix: false, },
        language: {
          column: "language.name",
          title: "Language",
          is_hidden: () => "appointment" != CN_session.get_leaf_model().get_name(),
        },
        phone: { column: "phone.name", title: "Phone Number" },
        user: { column: "user.name", title: "Reserved For" },
        assignment_user: { column: "assignment_user.name", title: "Assigned to" },
        state: {
          title: "State",
          table_prefix: false,
          help: "Will either be reached, not reached, upcoming, assignable, missed, assigned or in progress.",
        },
        interview_id: { is_hidden: () => true },
      },
      properties: {
        start_datetime: {
          meta: {}, // provided by the service
          title: "Start Date & Time",
          type: "datetime",
          is_constant: () => true,
          required: true,
          help: "Set by clicking a vacancy in the calendar below.",
        },
        duration: {
          meta: {}, // provided by the service
          title: "Duration",
          type: "enum",
          enum: {
            get_enums: () => {
              // add 8 increments for possible appointment lengths
              const interval = CN_session.get("setting", "vacancy_size");
              return CN_common.get_list_of_numbers(8).map(index => {
                const time = Number(interval * (index + 1));
                const hours = Math.floor(time / 60);
                const minutes = time % 60;
                return {
                  key: time,
                  value: (
                    (0 < hours ? hours + " hour" + (1 < hours ? "s" : "") : "") +
                    (0 < hours && 0 < minutes ? ", " : "") +
                    (0 < minutes ? minutes + " minute" + (1 < minutes ? "s" : "") : "")
                  ),
                };
              });
            }
          },
          get_default: () => CN_session.get("setting", "appointment_duration"),
          required: true,
          help: "Not all durations are necessarily available, check the vacancy calendar for details.",
        },
        participant: {
          meta: { table: "participant", column: "uid" },
          title: "Participant",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
        },
        qnaire: {
          meta: { table: "script", column: "name" },
          title: "Questionnaire",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
        },
        phone_id: {
          title: "Phone Number",
          type: "enum",
          enum: {
            get_enums: async (form_input) => {
              let enums = [];

              const parent_model = form_input.get_action().get_model().get_parent_model();
              const participant_id = (
                parent_model ?
                parent_model.get_action().get_property_value("participant_id") :
                null
              );

              if (null != participant_id) {
                const response = await CN_api.get(`participant/${participant_id}/phone`, {
                  select: { column: ["id", "rank", "type", "number"] },
                  modifier: {
                    where: { column: "phone.active", operator: "=", value: true },
                    order: "rank",
                  },
                });
                enums = response.map(record => ({
                  key: record.id,
                  value: `(${record.rank}) ${record.type}: ${record.number}`,
                }));
              }

              return enums;
            },
          },
          help: `
            Which number should be called for the appointment, or leave this field blank if any of the
            participant's phone numbers can be called.
          `,
        },
        user_id: {
          title: "Reserved for",
          type: "typeahead",
          typeahead: CN_model_user.get_typeahead(),
          help: `
            The user the appointment is specifically reserved for.
            Cannot be changed once the appointment has passed.
          `,
        },
        disable_mail: {
          meta: {}, // provided by the service
          title: "Disable Email Reminder(s)",
          type: "boolean",
          get_default: () => false,
          is_hidden: () => "view" == this.get_action_name(),
          required: true,
          help: "If selected then no automatic email reminders will be created for this appointment.",
        },
        assignment_user: {
          meta: { table: "assignment_user", column: "name" },
          title: "Assigned to",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
          help: `
            This will remain blank until the appointment has been assigned. The assigned user can only be
            different from the reserved user when the appointment was missed.
          `,
        },
        state: {
          meta: {}, // provided by the service
          title: "State",
          is_hidden: () => "add" == this.get_action_name(),
          is_constant: () => true,
          help: "One of reached, not reached, upcoming, assignable, missed, assigned or in progress.",
        },
        start_vacancy_id: {
          is_hidden: () => true,
        },
        participant_id: {
          meta: { table: "interview", column: "participant_id" },
          is_hidden: () => true,
        },
      },
      calendar: {
        select: {
          column: [
            "id", // appointment.id
            "interview_id",
            {
              column: `CONCAT(
                uid,
                " (", language.code, ")",
                " (", qnaire.rank, ")",
                IF(user_id, CONCAT(" for ", user.name), "")
              )`,
              alias: "title",
              table_prefix: false,
            },
            {
              column: `IF(
                "cancelled" = outcome,
                "secondary text-decoration-line-through",
                "primary"
              )`,
              alias: "type",
              table_prefix: false,
            },
            // start_datetime, end_datetime and help columns automatically provided
          ],
        },
        modifier: {
          order: ["start_datetime", "uid"],
        },
        on_click_event: async (event) => {
          await CN_session.navigate_to(`interview/view/${event.interview_id}/appointment/view/${event.id}`);
        },
      },
    });
  }

  /**
   * Extend parent method
   */
  allow_add() {
    const parent_model = this.get_parent_model();
    const parent_action = (
      null == parent_model || "interview" != parent_model.get_name() ?
      null :
      parent_model.get_action()
    );

    // Only allow an appointment to be added based on the parent interview's properties
    return (
      parent_action &&
      super.allow_add() && (
        // if there's no action then we're on the add appointment action
        null == this.get_action() ||
        // only allow the add button when the interview is open, has consent and has no future appointment
        (
          "(empty)" === parent_action.get_property_value("end_datetime") &&
          true === parent_action.get_property_value("last_participation_consent") &&
          false === parent_action.get_property_value("future_appointment")
        )
      )
    );
  }

  /**
   * Extend parent method
   */
  allow_delete() {
    const parent_model = this.get_parent_model();
    const parent_action = (
      null == parent_model || "interview" != parent_model.get_name() ?
      null :
      parent_model.get_action()
    );

    // only allow an appointment to be deleted when a future appointment exists
    return (
      parent_action &&
      super.allow_delete() &&
      true === parent_action.get_property_value("future_appointment")
    );
  }

  /**
   * Extend parent method
   */
  allow_edit() {
    // only allow editing future appointments
    let upcoming = false;
    const start_datetime = this.get_action().get_property_value("start_datetime");
    if (start_datetime && "(empty)" != start_datetime) {
      const date = new Date(start_datetime.replace(/ @ /, " "));
      const now = CN_common.get_date();
      now.setSeconds(0);
      upcoming = date >= now;
    }

    return super.allow_edit() && upcoming;
  }

  /**
   * ADD DOCS
   */
  async select_datetime_from_calendar(object, current_start_vacancy_id = null, duration = null) {
    // Note, if the current_start_vacancy_id is null then we are setting the datetime of a new appointment
    const new_appointment = null == current_start_vacancy_id;

    // check when editing an appointment that editing is allowed
    if (!new_appointment && !this.allow_edit()) {
      await CN_modal_message.create_and_open({
        header_class: "text-bg-danger",
        title: "Cannot Change",
        message: `
          You cannot change the start time of missed or cancelled appointment.
          You must instead create a new appointment.
        `,
      });
      return false;
    }

    // Do not allow selecting times in the past
    if (object.date < CN_common.get_date()) {
      await CN_modal_message.create_and_open({
        header_class: "text-bg-danger",
        title: "Invalid Appointment Time",
        message: `The time you have selected is in the past.  The appointment must be scheduled in the future.`,
      });
      return false;
    }

    // do nothing when selecting an existing appointments current vacancy
    if (!new_appointment && object.id && object.id == current_start_vacancy_id) return false;

    let result = null;
    if (object.id) {
      // check for vacancy
      const max_date = CN_common.clone(object.date);
      max_date.setMinutes(max_date.getMinutes() + duration);
      const response = await CN_api.count("vacancy", {
        select: { column: ["operators", "appointments", "datetime"] },
        modifier: {
          where: [
            { column: "datetime", operator: ">=", value: CN_common.format_datetime(object.date, "record") },
            { column: "datetime", operator: "<", value: CN_common.format_datetime(max_date, "record") },
            { column: "operators - appointments", operator: ">", value: 0, table_prefix: false },
          ],
        },
      });
      result = response == duration/CN_session.get("setting", "vacancy_size") ? "update" : "overbook";
    } else if (!object.id) {
      result = "overbook";
    }

    // make sure overbooking is allowed
    if (
      "overbook" == result &&
      2 > CN_session.get("role", "tier") &&
      "operator+" != CN_session.get("role", "name")
    ) {
      await CN_modal_message.create_and_open({
        header_class: "text-bg-danger",
        title: "No Vacancy",
        message: `
          The appointment time you have selected is missing vacancy.
          You may only schedule an appointment during a time block that has at least one operator available.
        `,
      });
      return false;
    }

    // confirm with the user, but only if we're overbooking or changing an existing appointment
    let proceed = true;
    if ("overbook" == result || !new_appointment) {
      let message = `
        Are you sure you wish to change the appointment's start time to
        ${CN_common.format_datetime(object.date, "datetime", true)}?
      `;
      if ("overbook" == result) {
        message = `
          NOTE: The time you have chosen will require the vacancy calendar to be overbooked.
          <br/>
          <br/>
          ${message}
        `;
      }

      proceed = await CN_modal_confirm.create_and_open({
        title: `${"overbook" == result ? "Overbook" : "Change"} Appointment`,
        message: message,
      });
    }
    return proceed;
  }
}

export class CN_add_appointment extends CN_action_add {
  #vacancy_model = null;

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();

    if (null == this.#vacancy_model) {
      const vacancy_module = CN_session.get_module("vacancy");
      await vacancy_module.load_classes();
      this.#vacancy_model = vacancy_module.create_model();
    }
  }

  /**
   * Extend parent method
   */
  async on_pre_submit(record) {
    await super.on_pre_submit(record);

    // add the start_vacancy_id property since it's a hidden property which doesn't get added to the record
    record.start_vacancy_id = this.get_property_value("start_vacancy_id");
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const model = this.get_model();

    // add the vacancy calendar if it hasn't been configured yet (once)
    if (this.#vacancy_model) {
      if (null != this.#vacancy_model.get_action()) {
        this.#vacancy_model.get_action().update_element();
      } else {
        this.#vacancy_model.configure(
          this.get_element(),
          "calendar",
          model.get_parent_model().get_action().get_property_value("effective_site_id"),
          null,
          true
        );

        // private function used by event listeners below
        const select_datetime_fn = async (object) => {
          const response = await model.select_datetime_from_calendar(
            object,
            null,
            this.get_property_value("duration")
          );

          if (response) {
            // warn if an appointment will be cancelled
            await this.set_property_value("start_datetime", CN_common.format_datetime(object.date, "record"));
            await this.on_set_property("start_datetime");

            if (object.id) {
              await this.set_property_value("start_vacancy_id", object.id);
              await this.on_set_property("start_vacancy_id");
            }
          }
        }

        // change the calendar's events to act as a way to set the appointment's datetime
        const vacancy_action = this.#vacancy_model.get_action();
        vacancy_action.set_config("on_select", null);
        vacancy_action.set_config("on_click_cell", select_datetime_fn);
        vacancy_action.set_config("on_click_event", select_datetime_fn);
      }

      this.get_element().append(this.#vacancy_model.get_element());
    }
  }

  /**
   * Extend parent method
   */
  async run() {
    await super.run();
    await this.#vacancy_model.run();
  }
}

export class CN_calendar_appointment extends CN_action_calendar {
  #site_list = [];

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("header" == type) {
      const site_id = this.get_model().get_identifier();
      const response = await CN_api.get(`site/${site_id}`);

      const title = await super.get_text(type);
      return `${response.name} ${title}`;
    }

    return await super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_load() {
    const site_id = this.get_model().get_identifier();

    if (CN_session.get("role", "all_sites")) {
      // populate the site list for calendar switching
      this.#site_list = await CN_api.get("site", {
        select: { column: ["id", "name"] },
        modifier: { order: "name" },
      });
    } else if (site_id != CN_session.get("site", "id")) {
      // check site access
      const error = new URIError();
      error.title = "Page not found (403)";
      error.message = "You do not have access to the requested resource.";
      throw error;
    }

    await super.on_load();
  }

  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    const parameters = super.get_on_load_parameters();
    parameters.restricted_site_id = this.get_model().get_identifier();
    return parameters;
  }

  /**
   * Extend parent method
   */
  _create_header_element() {
    const header_el = super._create_header_element();

    if (CN_session.get("role", "all_sites")) {
      const site_div_el = this.constructor.html(`
        <div class="dropdown" name="site">
          <button name="site" type="button" class="btn btn-primary px-2 py-0" data-bs-toggle="dropdown">
            <i class="bi bi-calendar fs-5"></i>
          </button>
          <ul class="dropdown-menu bg-secondary">
            <li><div class="dropdown-header text-bg-secondary">Site Calendars</div></li>
          </ul>
        </div>
      `);

      const site_id = this.get_model().get_identifier();
      this.#site_list.forEach(site => {
        const site_btn_el = this.constructor.html(`
          <button type="button" class="dropdown-item">${site.name}</button>
        `);
        site_btn_el.addEventListener("click", () => {
          const calendar_params = this.get_query_parameter("calendar");
          CN_session.navigate_to(
            `appointment/calendar/${site.id}`,
            calendar_params ? { calendar: calendar_params } : null,
          );
        });
        const site_li_el = this.constructor.html(
          `<li class="bg-${site.id == site_id ? "warning" : "body"}"></li>`
        );
        site_li_el.append(site_btn_el);
        site_div_el.querySelector("ul").append(site_li_el);
      });

      header_el.querySelector("div[name=report]").before(site_div_el);
    }

    return header_el;
  }

  /**
   * Extend parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    // add the appointment/vacancy calendar buttons (if the user has access to them)
    const utilities = CN_session.get("menu", "utilities");
    if (utilities["Appointment Calendar"] && utilities["Vacancy Calendar"]) {
      const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

      const appointment_btn_el = this.constructor.html(
        '<button type="button" name="appointment" class="btn btn-warning">Appointment</button>'
      );
      left_btn_group_el.append(appointment_btn_el);
      appointment_btn_el.addEventListener("click", () => {
        const calendar_params = this.get_query_parameter("calendar");
        CN_session.navigate_to(
          `appointment/calendar/${this.get_model().get_identifier()}`,
          calendar_params ? { calendar: calendar_params } : null,
        );
      });

      const vacancy_btn_el = this.constructor.html(
        '<button type="button" name="vacancy" class="btn btn-light btn-outline-primary">Vacancy</button>'
      );
      left_btn_group_el.append(vacancy_btn_el);
      vacancy_btn_el.addEventListener("click", () => {
        const calendar_params = this.get_query_parameter("calendar");
        CN_session.navigate_to(
          `vacancy/calendar/${this.get_model().get_identifier()}`,
          calendar_params ? { calendar: calendar_params } : null,
        );
      });
    } else {
      footer_el.querySelector("button[name=list]").remove();
    }

    return footer_el;
  }
}

export class CN_list_appointment extends CN_action_list {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // add the appointment calendar button when viewing the base appointment list
    const btn_group_el = this.get_footer_element().querySelector("div.btn-group");
    if (null == this.get_model().get_parent_model() && !btn_group_el.querySelector("button[name=calendar]")) {
      const calendar_btn_el = this.constructor.html(
        '<button type="button" name="calendar" class="btn btn-primary">Appointment Calendar</button>'
      );
      btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", () => {
        CN_session.navigate_to(`appointment/calendar/${this.get_model().get_identifier()}`)
      });
    }
  }

  /**
   * Replace parent method
   */
  async on_row_click(record) {
    // always include the interview as the parent model when selecting an appointment
    await CN_session.navigate_to(`interview/view/${record.interview_id}/appointment/view/${record.id}`);
  }
}

export class CN_view_appointment extends CN_action_view {
  #vacancy_model = null;

  /**
   * Extend parent method
   */
  async on_load() {
    // do not allow loading an appointment with no parent model
    if (null == this.get_model().get_parent_model()) throw new URIError();

    await super.on_load();

    if (null == this.#vacancy_model) {
      const vacancy_module = CN_session.get_module("vacancy");
      await vacancy_module.load_classes();
      this.#vacancy_model = vacancy_module.create_model();
    }
  }

  /**
   * Extend parent method
   */
  async on_set_property(prop_name, run = true) {
    await this.constructor.wait_for(super.on_set_property(prop_name, run));
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const model = this.get_model();

    // only include the cancel button when the appointment is assignable or missed
    const cancel_btn_el = this.get_footer_element().querySelector("button[name=cancel]");
    if (["assignable", "missed"].includes(this.get_property_value("state"))) {
      cancel_btn_el.classList.remove("d-none");
    } else {
      cancel_btn_el.classList.add("d-none");
    }

    // add the vacancy calendar if it hasn't been configured yet (once)
    if (this.#vacancy_model) {
      if (null != this.#vacancy_model.get_action()) {
        this.#vacancy_model.get_action().update_element();
      } else {
        this.#vacancy_model.configure(
          this.get_element(),
          "calendar",
          model.get_parent_model().get_action().get_property_value("effective_site_id"),
          null,
          true
        );

        // private function used by event listeners below
        const select_datetime_fn = async (object) => {
          const response = await model.select_datetime_from_calendar(
            object,
            this.get_property_value("start_vacancy_id"),
            this.get_property_value("duration")
          );

          if (response) {
            await this.constructor.wait_for(async() => {
              // update the appointment's start vacancy if a vacancy was selected, otherwise use the datetime
              const data = {};
              if (object.id) {
                data.start_vacancy_id = object.id;
              } else {
                data.start_datetime = CN_common.format_datetime(object.date, "record");
              }

              const path = `appointment/${model.get_identifier()}`;
              await CN_api.patch(path, data);

              // PLEASE NOTE:
              // The "update_email" option is used to update an appointment's mail reminders after the start
              // vacancy has been changed.  We can't do this at the time that the vacancy is changed because
              // the start_vacancy_id column is updated as part of a trigger, so the software layer won't be
              // aware of the change until after the process which made the change is complete.
              // Therefore, an additional request must be made after the change in start vacancy.
              await CN_api.patch(`${path}?update_mail=1`, {});

              await this.run();
            }, 0);
          }
        };

        // change the calendar's events to act as a way to set the appointment's datetime
        const vacancy_action = this.#vacancy_model.get_action();
        vacancy_action.set_config("on_select", null);
        vacancy_action.set_config("on_click_cell", select_datetime_fn);
        vacancy_action.set_config("on_click_event", select_datetime_fn);
      }

      this.get_element().append(this.#vacancy_model.get_element());
    }
  }

  /**
   * Add operation to footer element
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]")

    // add the notes action
    const notes_btn_el = this.constructor.html(
      '<button name="notes" type="button" class="btn btn-light btn-outline-primary">Notes</button>'
    );
    notes_btn_el.addEventListener("click", () => {
      CN_session.navigate_to(`participant/notes/${this.get_property_value("participant_id")}`);
    });
    left_btn_group_el.append(notes_btn_el);

    // add the cancel action
    const cancel_btn_el = this.constructor.html(
      '<button name="cancel" type="button" class="btn btn-warning">Cancel</button>'
    );
    cancel_btn_el.addEventListener("click", async () => {
      await this.constructor.wait_for(async() => {
        await CN_api.patch(this.get_model().get_view_url(null, "api"), { outcome: "cancelled" });
        await this.run();
      });
    });
    left_btn_group_el.append(cancel_btn_el);

    // add a button to the appointment calendar (if the user has access)
    if (CN_session.get("menu", "utilities")["Appointment Calendar"]) {
      const calendar_btn_el = this.constructor.html(`
        <button name="calendar" type="button" class="btn btn-light btn-outline-primary">
          Appointment Calendar
        </button>
      `);
      calendar_btn_el.addEventListener("click", async () => {
        const response = await CN_api.get(`participant/${this.get_property_value("participant_id")}`, {
          select: { column: { table: "site", column: "id", alias: "site_id" } },
        });
        CN_session.navigate_to(`appointment/calendar/${response.site_id}`);
      });
      left_btn_group_el.append(calendar_btn_el);
    }

    return footer_el;
  }

  /**
   * Extend parent method
   */
  async run() {
    await super.run();
    await this.#vacancy_model.run();
  }
}
