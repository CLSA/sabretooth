const { CN_base_action } = await import(`${CENOZO_URL}/js/action/base_action.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_input_datetimesecond } = await import(`${CENOZO_URL}/js/input/datetimesecond.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_queue extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "queue",
        plural: "queues",
        posessive: "queue's",
      },
      columns: {
        rank: { title: "Rank", type: "rank" },
        name: { title: "Name" },
        participant_count: { title: "Participants", type: "integer", table_prefix: false },
      },
      properties: {
        rank: { title: "Rank", type: "rank", is_constant: () => true },
        name: { title: "Name", is_constant: () => true },
        title: { title: "Title", is_constant: () => true },
        description: { title: "Description", type: "text", is_constant: () => true },
      },
    });
  }
}

export class CN_tree_queue extends CN_base_action {
  constructor(parent_el, model) {
    super("tree", parent_el, model);
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if (["crumb", "header"].includes(type)) {
      return "Queue Tree";
    }

    return super.get_text(type);
  }

  /**
   * Extend parent method
   */
  _create_body_element() {
    const body_el = this.constructor.html(`
      <div class="row">
        <div name="settings" class="col-sm-4">
        </div>
        <div name="tree" class="col-sm-8">
        </div>
      </div>
    `);

    const settings_el = body_el.querySelector("div[name=settings]");

    // add last re-populated time input
    let row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "repopulated",
      value: "Last Re-Population",
    }).get_element().classList.remove("text-end");
    CN_input_datetimesecond.append(row_el, {
      id: "repopulated",
      disabled: true,
    });
    settings_el.append(row_el);

    if (3 <= CN_session.get("role", "tier")) {
      // add re-populate button
      row_el = this.constructor.html(`
        <div class="row mx-0 my-3">
          <button type="button" name="repopulate" class="btn btn-warning">Re-Populate</button>
          <div class="alert alert-info" role="alert">
            The queue tree automatically re-populates itself once a day.
            The last time the queue was re-popluated is shown above.
            The queue tree can be manually re-populated by clicking the button above.
          </div>
        </div>
      `);
      settings_el.append(row_el);
    }

    // add qnaire input
    row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "qnaire_id",
      value: "Questionnaire",
    }).get_element().classList.remove("text-end");
    const qnaire_id_form_input = CN_input_enum.append(row_el, {
      id: "qnaire_id",
      enum: { path: "qnaire" },
      empty_label: "Any",
    });

    settings_el.append(row_el);

    // add site input
    if (CN_session.get("role", "all_sites")) {
      row_el = this.constructor.html('<div class="row"></div>');
      CN_element_label.append(row_el, {
        for: "site_id",
        value: "Site",
      }).get_element().classList.remove("text-end");
      CN_input_enum.append(row_el, {
        id: "site_id",
        enum: { path: "site" },
        empty_label: "Any",
      });
      settings_el.append(row_el);
    }

    // add language input
    row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "language_id",
      value: "Participant Language",
    }).get_element().classList.remove("text-end");
    CN_input_enum.append(row_el, {
      id: "language_id",
      enum: { path: "language" },
      empty_label: "Any",
    });
    settings_el.append(row_el);

    return body_el;
  }
}
