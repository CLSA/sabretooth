const { CN_action_calendar } = await import(`${CENOZO_URL}/js/action/calendar.mjs`);
const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_model_base } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const { CN_model_user } = await import(`${CENOZO_URL}/js/model/user.mjs`);

export class CN_model_appointment extends CN_model_base {
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
          is_hidden: (model) => "appointment" != CN_session.get_leaf_model().get_name(),
        },
        phone: { column: "phone.name", title: "Phone Number" },
        user: { column: "user.name", title: "Reserved For" },
        assignment_user: { column: "assignment_user.name", title: "Assigned to" },
        state: {
          title: "State",
          table_prefix: false,
          help: "Will either be reached, not reached, upcoming, assignable, missed, assigned or in progress",
        },
      },
      properties: {
        start_datetime: {
          meta: {}, // provided by the service
          title: "Start Date & Time",
          type: "datetime",
          is_constant: () => true,
          help: "Set by clicking a vacancy in the calendar below",
        },
        duration: {
          meta: {}, // provided by the service
          title: "Duration",
          type: "enum",
          enum: {
            get_enums: (model) => {
              // add 8 increments for possible appointment lengths
              var interval = CN_session.get("setting", "vacancy_size");
              return CN_common.get_list_of_numbers(8).map(index => {
                var time = interval * (index + 1);
                var hours = Math.floor(time / 60);
                var minutes = time % 60;
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
          help: "Not all durations are necessarily available, check the vacancy calendar for details",
        },
        participant: {
          meta: { table: "participant", column: "uid" },
          title: "Participant",
          is_hidden: (model) => "add" == model.get_action_name(),
          is_constant: () => true,
        },
        qnaire: {
          meta: { table: "script", column: "name" },
          title: "Questionnaire",
          is_hidden: (model) => "add" == model.get_action_name(),
          is_constant: () => true,
        },
        phone_id: {
          title: "Phone Number",
          type: "enum",
          enum: {
            get_enums: async (model) => {
              const participant_id = model.get_action().get_property_value("participant_id");

              let enums = [];
              if (null != participant_id) {
                const response = await CN_api.get(
                  `participant/${model.get_action().get_property_value("participant_id")}/phone`,
                  {
                    select: { column: ["id", "rank", "type", "number"] },
                    modifier: {
                      where: { column: "phone.active", operator: "=", value: true },
                      order: "rank",
                    },
                  },
                );

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
          is_hidden: (model) => "view" == model.get_action_name(),
          help: "If selected then no automatic email reminders will be created for this appointment.",
        },
        assignment_user: {
          meta: { table: "assignment_user", column: "name" },
          title: "Assigned to",
          is_hidden: (model) => "add" == model.get_action_name(),
          is_constant: () => true,
          help: `
            This will remain blank until the appointment has been assigned. The assigned user can only be
            different from the reserved user when the appointment was missed.
          `,
        },
        state: {
          meta: {}, // provided by the service
          title: "State",
          is_hidden: (model) => "add" == model.get_action_name(),
          is_constant: () => true,
          help: "One of reached, not reached, upcoming, assignable, missed, assigned or in progress",
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
            {
              column: "CONCAT(uid, ' (', language.code, ') (', qnaire.rank ,')')",
              alias: "title",
              table_prefix: false
            },
            // start_datetime, end_datetime and help columns automatically provided
          ],
        },
        modifier: {
          order: ["start_datetime", "uid"],
        },
        on_click: async (event) => {
          await CN_session.navigate_to(`appointment/view/${event.id}`);
        },
      },
    });
  }

  /**
   * Extend parent method
   */
  allow_delete() {
    return false;
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
      const now = new Date();
      now.setSeconds(0);
      upcoming = date >= now;
    }

    return super.allow_edit() && upcoming;
  }
}

export class CN_calendar_appointment extends CN_action_calendar {
  /**
   * Extend parent method
   */
  create_footer_element() {
    const footer_el = super.create_footer_element();

    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

    const appointment_btn_el = this.constructor.html(
      '<button name="appointment" class="btn btn-warning">Appointment</button>'
    );
    left_btn_group_el.append(appointment_btn_el);
    this.constructor.set_disabled(appointment_btn_el, true);

    const vacancy_btn_el = this.constructor.html(
      '<button name="vacancy" class="btn btn-light btn-outline-primary">Vacancy</button>'
    );
    left_btn_group_el.append(vacancy_btn_el);
    vacancy_btn_el.addEventListener("click", () => {
      const calendar_params = this.get_query_parameter("calendar");
      CN_session.navigate_to(
        `vacancy/calendar/${this.get_model().get_identifier()}`,
        calendar_params ? { calendar: calendar_params } : null,
      );
    });

    return footer_el;
  }
}

export class CN_list_appointment extends CN_action_list {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // replace the add button with a calendar button instead
    const btn_group_el = this.get_footer_element().querySelector("div.btn-group");
    const add_btn_el = btn_group_el.querySelector("button[name=add]");
    if (add_btn_el) add_btn_el.remove();

    if (!btn_group_el.querySelector("button[name=calendar]")) {
      const calendar_btn_el = this.constructor.html(
        '<button name="calendar" class="btn btn-primary">Appointment Calendar</button>'
      );
      btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", () => {
        CN_session.navigate_to(`appointment/calendar/${this.get_model().get_identifier()}`)
      });
    }
  }
}

export class CN_view_appointment extends CN_action_view {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // only include the cancel button when the appointment is assignable or missed
    const cancel_btn_el = this.get_footer_element().querySelector("button[name=cancel]");
    if (["assignable", "missed"].includes(this.get_property_value("state"))) {
      cancel_btn_el.classList.remove("d-none");
    } else {
      cancel_btn_el.classList.add("d-none");
    }
  }

  /**
   * Add operation to footer element
   */
  create_footer_element() {
    const footer_el = super.create_footer_element();
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
        this.get_property("state").form_input.clear_value(); // do this so the undo button doesn't appear
        await this.run();
      });
    });
    left_btn_group_el.append(cancel_btn_el);

    // add the calendar action
    const calendar_btn_el = this.constructor.html(`
      <button name="calendar" type="button" class="btn btn-light btn-outline-primary">
        Appointment Calendar
      </button>
    `);
    calendar_btn_el.addEventListener("click", async () => {
      const response = await CN_api.get(`participant/${this.get_property_value("participant_id")}`, {
        select: { column: { table: "site", column: "name" } },
      });
      CN_session.navigate_to(`appointment/calendar/name=${response.name}`);
    });
    left_btn_group_el.append(calendar_btn_el);

    return footer_el;
  }
}

