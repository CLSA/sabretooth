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
