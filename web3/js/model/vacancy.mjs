const { CN_action_calendar } = await import(`${CENOZO_URL}/js/action/calendar.mjs`);
const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_model_base } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_modal_input } = await import(`${CENOZO_URL}/js/modal/input.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_vacancy extends CN_model_base {
  constructor() {
    super({
      wording: {
        singular: "vacancy",
        plural: "vacancies",
        posessive: "vacancy's",
      },
      columns: {
        datetime: { type: "datetime", title: "Date & Time" },
        operators: { title: "Operators" },
      },
      properties: {
        datetime: {
          title: "Date & Time",
          type: "datetime",
          // minuteStep: 60, // TODO: need to implement missing feature
          is_constant: (model) => 0 < model.get_action().get_property_value("appointments"),
          help: "Can only be changed if the vacancy has no appointments.",
        },
        operators: {
          title: "Operators",
          format: "integer",
          minValue: 1,
          help: "How many operators are available at this time",
        },
      },
      calendar: {
        mode: "week",
        select: {
          column: [
            "id", // vacancy.id
            "datetime",
            {
              column: "CONCAT(appointments, ' of ', operators, ' booked')",
              alias: "title",
              table_prefix: false
            },
            {
              column:
                'IF(appointments > operators, "danger", IF(appointments < operators, "primary", "secondary"))',
              alias: "type",
              table_prefix: false
            },
            { column: "30", alias: "duration", table_prefix: false },
            "appointments", // used to prevent deleting vacancies that have been filled
          ],
        },
        modifier: {
          order: "datetime",
        },
        on_click: async (event) => {
          await CN_session.navigate_to(`vacancy/view/${event.id}`);
        },
        on_select: async (model, dates, events) => {
          if (0 < dates.length && "month" != model.get_action().get_mode()) {
            // divide dates into blocks
            let last_date = null;
            const blocks = dates.reduce((list, date) => {
              // create a date at the end of the current date's block
              const end_date = CN_common.clone(date);
              end_date.setMinutes(end_date.getMinutes() + 30);

              // if this date is more than 30 minutes ahead of the last then start a new block
              if (null == last_date || 30 < (date - last_date)/60000) {
                list.push({
                  start: CN_common.clone(date),
                });
              }

              // set the end of the current block
              list[list.length - 1].end = end_date;

              last_date = date;
              return list;
            }, []);

            const li_list = blocks.map(block => `
              <li>
                <span class="fw-bold">${CN_common.format_datetime(block.start, "date", true)}</span> from
                <span class="fw-bold">${CN_common.format_time(block.start)}</span> until
                <span class="fw-bold">${CN_common.format_time(block.end)}</span>
              </li>
            `);

            // get the number of operators from the user
            const response = await CN_modal_input.create_and_open({
              title: `Create Vacancy Block${1 == blocks.length ? "" : "s"}`,
              message: `
                <div>
                  You have selected the following block${1 == blocks.length ? "" : "s"} of vacancies:<br/>
                  <ul>${li_list.join("\n")}</ul>
                </div>
                <div>
                  If you wish to proceed please provide the number of operators that will be available:
                </div>
              `,
              input: { type: "integer", get_default: () => 1, min: 1 },
            });

            if (0 < response) {
              await Promise.all(
                blocks.map(block => CN_api.post("vacancy", {
                  start_datetime: CN_common.format_datetime(block.start, "record"),
                  end_datetime: CN_common.format_datetime(block.end, "record"),
                  operators: response,
                }))
              );
            }
          }

          if (0 < events.length) {
            const events_to_delete = events.filter(event => 0 == event.appointments);
            const vacancies = 1 == events.length ? model.get_singular() : model.get_plural();
            const delete_vacancies = 1 == events_to_delete.length ? model.get_singular() : model.get_plural();

            if (0 == events_to_delete.length) {
              await CN_modal_message.create_and_open({
                title: `Cannot Delete ${CN_common.uc_words(vacancies)}`,
                message: (
                  1 == events.length ?
                  "The vacancy you selected cannot be deleted because it already has scheduled appointments." :
                  `
                    The ${events.length} vacancies you have selected cannot be deleted
                    because they already have scheduled appointments.
                  `
                ),
                type: "error",
              });
            } else {
              const response = await CN_modal_confirm.create_and_open({
                title: "Please Confirm",
                message: (
                  events.length == events_to_delete.length ?
                  `Would you like to delete the ${events.length} ${vacancies} you have selected?` :
                  `
                    Would you like to delete the ${events_to_delete.length} ${delete_vacancies} that you have
                    selected which do not have any appointments?
                    <br/><br/>
                    Note: only vacancies which do not appointments will be deleted.
                  `
                ),
              });

              // remove all selected vacancies
              if (response) await CN_api.post("vacancy", { delete_ids: events_to_delete.map(event => event.id) });
            }
          }

          await this.run();
        },
      },
    });
  }
}

export class CN_calendar_vacancy extends CN_action_calendar {
  /**
   * Extend parent method
   */
  create_footer_element() {
    const footer_el = super.create_footer_element();

    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

    const appointment_btn_el = this.constructor.html(
      '<button name="appointment" class="btn btn-light btn-outline-primary">Appointment</button>'
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
      '<button name="vacancy" class="btn btn-warning">Vacancy</button>'
    );
    left_btn_group_el.append(vacancy_btn_el);
    this.constructor.set_disabled(vacancy_btn_el, true);

    return footer_el;
  }
}

export class CN_list_vacancy extends CN_action_list {
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
        '<button name="calendar" class="btn btn-primary">Vacancy Calendar</button>'
      );
      btn_group_el.append(calendar_btn_el);
      calendar_btn_el.addEventListener("click", () => {
        CN_session.navigate_to(`vacancy/calendar/${this.get_model().get_identifier()}`)
      });
    }
  }
}
