const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/participant.mjs`);

export class CN_model_participant extends classes.CN_model_participant {
  /**
   * Extend parent method
   */
  async clone_columns() {
    const columns = await super.clone_columns();

    // add qnaire and language columns to the queue's participant list
    if ("queue" == CN_session.get_leaf_model().get_name()) {
      CN_common.insert_property(
        columns,
        "before",
        "uid",
        "qnaire",
        { title: "Questionnaire", column: "script.name" }
      );
      CN_common.insert_property(
        columns,
        "before",
        "uid",
        "language",
        { title: "Language", column: "language.name" },
      );
    }

    return columns;
  }
  /**
   * Extend parent method
   */
  async clone_properties() {
    const properties = await super.clone_properties();

    properties.queue_details = {
      title: "Queue Details",
      properties: {
        title: {
          title: "Current Questionnaire",
          meta: { table: "qnaire", column: "title" },
          is_constant: () => true,
        },
        start_date: {
          title: "Delayed Until",
          meta: { table: "qnaire", column: "start_date" },
          type: "date",
          is_constant: () => true,
          help:
            "If not empty then the participant will not be permitted to begin this questionnaire until the " +
            "date shown is reached.",
        },
        queue: {
          title: "Current Queue",
          meta: { table: "queue", column: "title" },
          is_constant: () => true,
        },
        override_stratum: {
          title: "Override Stratum",
          type: "boolean",
          is_constant: () => 3 > CN_session.get("role", "tier"),
        },
      },
    };

    return properties;
  }
}

export class CN_history_participant extends classes.CN_history_participant {
  constructor(parent_el, model) {
    super(parent_el, model);

    this.add_category({
      subject: "appointment",
      get_data: async function () {
        try {
          // get appointments from all interviews
          return (
            await Promise.all(
              // get a list this participant's interviews and convert to API requests for their appointments
              (await CN_api.get(
                `${model.get_view_url(null, "api")}/interview`,
                { select: { column: "id" } }
              )).map(row => CN_api.get(`interview/${row.id}/appointment`, {
                select: { column: [
                  "outcome",
                  "assignment_id",
                  "user_id",
                  { table: "vacancy", column: "datetime" },
                  { table: "user", column: "first_name", alias: "user_first" },
                  { table: "user", column: "last_name", alias: "user_last" },
                ]},
                modifier: {
                  join: { table: "vacancy", onleft: "appointment.start_vacancy_id", onright: "vacancy.id" },
                },
              }))
            )
          ).reduce((list, rows) => {
            rows.forEach(row => {
              list.push({
                category: this,
                datetime: row.datetime,
                title: `scheduled for ${
                  null == row.user_id ?
                  "any operator" :
                  `${row.user_first} ${row.user_last}`
                }`,
                description:
                  `An appointment scheduled for this time has ${
                    "cancelled" == row.outcome ?  "been cancelled" :
                    row.assignment_id ?  `been met.\nDuring the call the participant was ${row.outcome}` :
                    "not yet been met"
                  }.`,
              });
            });
            return list;
          }, []);
        } catch (error) {
          // ignore 404s, it just means we don't have access to reading the data
          if (!CN_common.is_uri_error(error, 404)) throw error;
        }

        // return an empty list if the data failed to load
        return [];
      },
    });

    this.add_category({
      subject: "assignment",
      get_data: async function () {
        try {
          // get assignments from all interviews
          return (
            await Promise.all(
              // get a list this participant's interviews and convert to API requests for their appointments
              (await CN_api.get(
                `${model.get_view_url(null, "api")}/interview`,
                { select: { column: "id" } }
              )).map(row => CN_api.get(`interview/${row.id}/assignment`, {
                select: { column: [
                  "start_datetime",
                  "end_datetime",
                  { table: "user", column: "first_name", alias: "user_first" },
                  { table: "user", column: "last_name", alias: "user_last" },
                  { table: "site", column: "name", alias: "site" },
                  { table: "script", column: "name", alias: "script" },
                  { table: "queue", column: "name", alias: "queue" },
                ]},
              }))
            )
          ).reduce((list, rows) => {
            rows.forEach(row => {
              list.push({
                category: this,
                datetime: row.start_datetime,
                title: `started by ${row.user_first} ${row.user_last}`,
                description:
                  `Started an assignment for the "${row.script}" questionnaire.\n` +
                  `Assigned from the ${row.site} site from the ${row.queue} queue.`,
              });
              list.push({
                category: this,
                datetime: row.end_datetime,
                title: `completed by ${row.user_first} ${row.user_last}`,
                description:
                  `Completed an assignment for the "${row.script}" questionnaire.\n` +
                  `Assigned from the ${row.site} site from the ${row.queue} queue.`,
              });
            });
            return list;
          }, []);
        } catch (error) {
          // ignore 404s, it just means we don't have access to reading the data
          if (!CN_common.is_uri_error(error, 404)) throw error;
        }

        // return an empty list if the data failed to load
        return [];
      },
    });
  }
}

export class CN_view_participant extends classes.CN_view_participant {
  /**
   * Extend parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

    if ("operator" != CN_session.get("role", "name")) {
      const update_queue_btn_el = this.constructor.html(
        '<button name="update-queue" type="button" class="btn btn-light btn-outline-primary">Update Queue</button>'
      );
      update_queue_btn_el.addEventListener("click", () => {
        this.constructor.wait_for(Promise.all([
          CN_api.patch(`participant/${this.get_model().get_identifier()}?repopulate=1`, {}),
          this.run(),
        ]), 0);
      });
      left_btn_group_el.append(update_queue_btn_el);
    }

    return footer_el;
  }
}
