const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_base_action } = await import(`${CENOZO_URL}/js/action/base_action.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_element_card } = await import(`${CENOZO_URL}/js/element/card.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_element_participant_selection }  = await import(`${CENOZO_URL}/js/model/participant.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_modal_message } = await import(`${CENOZO_URL}/js/modal/message.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_qnaire extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "questionnaire",
        plural: "questionnaires",
        posessive: "questionnaire's",
      },
      columns: {
        name: { column: "script.name", title: "Name" },
        rank: { title: "Rank", type: "rank" },
        allow_missing_consent: { title: "Missing Consent", type: "boolean" },
        web_version: { title: "Web Version", type: "boolean" },
        delay_offset: { title: "Delay Offset", type: "integer" },
        delay_unit: { title: "Delay Unit" },
      },
      properties: {
        rank: { meta: { table: "qnaire", column: "rank" }, title: "Rank", type: "rank" },
        script_id: {
          title: "Script",
          type: "enum",
          enum: {
            path: "application/0/script",
            modifier: {
              where: [{ column: "repeated", operator: "=", value: false }],
              order: "name",
              limit: 1000,
            },
          },
          is_constant: model => "view" == model.get_action_name(),
          help: "Only scripts which are marked as non-repeatable may be used as a questionnaire.",
        },
        allow_missing_consent: {
          title: "Allow Missing Consent",
          type: "boolean",
          help: `
            This field determines whether or not a participant should be allowed to proceed with the
            questionnaire when they are missing the extra consent record specified by the study.
          `,
        },
        web_version: {
          title: "Web Version",
          type: "boolean",
          is_constant: model =>
            "add" != model.get_action_name() &&
            null == model.get_action().get_property_value("pine_qnaire_id"),
          help: "Defines whether this questionnaire has a web-version.",
        },
        delay_offset: { title: "Delay Offset", format: "integer", get_min: 0 },
        delay_unit: { title: "Delay Unit", type: "enum" },
        pine_qnaire_id: { meta: { table: "script", column: "pine_qnaire_id" }, is_hidden: () => true, },
      },
    });
  }
}

export class CN_mass_method_qnaire extends CN_base_action {
  #qnaire_name = null;
  #method_form_input = null;
  #participant_selection = new CN_element_participant_selection(null, {
    data: {
      method: "phone",
      mode: "confirm",
    },
  });

  /**
   * Constructor
   * @param base_model model: The model that the action belongs to
   */
  constructor(parent_el, model) {
    super("mass_method", parent_el, model);
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if ("crumb" == type) {
      return `Mass Interview Method`;
    }

    if ("header" == type) {
      // load the qnaire details and site list
      await this.after_first_load();
      return `Mass Interview "${this.#qnaire_name}"`;
    }

    return super.get_text(type);
  }

  /**
   * Extend parent method
   */
  async on_navigate_to_parent() {
    await CN_session.navigate_to(this.get_model().get_view_url());
  }

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();

    // reset the list and confirm components
    await this.#participant_selection.reset();

    const response = await CN_api.get(
      `qnaire/${this.get_model().get_identifier()}`,
      { select: { column: "name" } }
    );
    this.#qnaire_name = response.name;
  }

  /**
   * Extend parent method
   */
  create_body_element() {
    const body_el = this.constructor.html(`
      <div class="container-fluid text-info-emphasis">
        <div class="pb-2">
          This utility allows you change the interview method for multiple participants for the
          selected questionnaire.
          In order to proceed you must first select which participants to affect.
          This can be done by typing the unique identifiers (ie: A123456) of all participants you wish
          to have included in the operation, then confirm that list to ensure each of the identifiers
          can be linked to a participant.
        </div>
        <div name="method" class="row py-1"></div>
        <div name="participant-list" class="py-1"></div>
        <div name="participant-confirm" class="py-1 d-none"></div>
      </div>
    `);

    const method_el = body_el.querySelector("div[name=method]");
    CN_element_label.append(method_el, {
      for: "method",
      value: "Interview Method",
      class: "col-sm-3",
    });
    this.#method_form_input = new CN_input_enum(method_el, {
      id: "method",
      class: "col-sm-9",
      required: true,
      get_default: () => "phone",
      enum: { values: [{ key: "phone", value: "Phone" }, { key: "web", value: "Web" }] },
      on_input: async (form_input) => {
        this.#participant_selection.set_config("data", { method: form_input.get_value(), mode: "confirm" });
        this.#participant_selection.reset_confirmation();
      },
    });
    method_el.append(this.#method_form_input.get_element());

    const footer_el = this.constructor.html('<div class="row"></div>');
    const proceed_btn_el = this.constructor.html(
      '<button name="proceed" type="button" class="btn btn-primary">Proceed</button>'
    );
    proceed_btn_el.addEventListener("click", async () => {
      const method = this.#method_form_input.get_value();
      const identifier_list = this.#participant_selection.get_identifier_list();
      if (0 == identifier_list.length) return;

      await this.constructor.wait_for(async () => {
        CN_api.post(`qnaire/${this.get_model().get_identifier()}/participant`, {
          mode: "update",
          identifier_id: this.#participant_selection.get_idtype(),
          identifier_list: identifier_list,
          method: method,
        });
      });

      await CN_modal_message.create_and_open({
        title: "Interview Methods Updated",
        message: `
          You ahve successfully changed ${identifier_list.length} "${this.#qnaire_name}"
          interview${1 == identifier_list.length ? "" : "s"} to using the ${method} interviewing method.
        `,
      });

      this.#participant_selection.reset();
    });
    footer_el.append(proceed_btn_el);

    CN_element_card.append(body_el.querySelector("[name=participant-confirm]"), {
      header: "Confirm Selection",
      body: "",
      footer: footer_el,
    });

    this.#participant_selection.set_config("path", `qnaire/${this.get_model().get_identifier()}/participant`);
    this.#participant_selection.add_event_listener("selectionchanged", () => {
      const confirm_el = body_el.querySelector("[name=participant-confirm]");
      const summary_el = confirm_el.querySelector("div.card-body");
      summary_el.innerHTML = "";
      const selected_participants = this.#participant_selection.get_identifier_list().length;
      if (0 < selected_participants) {
        confirm_el.classList.remove("d-none");
        confirm_el.querySelector("div.card-body").innerHTML = `
          You have selected a total of ${selected_participants}
          participant${1 == selected_participants ? "" : "s"} to change the interviewing method for.
          If you wish to proceed you may select the interview method and click the "Proceed" button below,
          or you may make changes to the participant selection list above.
        `;
      } else {
        confirm_el.classList.add("d-none");
        confirm_el.querySelector("div.card-body").innerHTML = "";
      }
    });

    const participant_list_el = body_el.querySelector("[name=participant-list]");
    this.#participant_selection.set_parent_element(participant_list_el);
    participant_list_el.append(this.#participant_selection.get_element());

    return body_el;
  }

  /**
   * Extend parent method
   */
  create_footer_element() {
    const footer_el = this.constructor.html(`
      <div class="btn-group" role="group">
        <button name="back" type="button" class="btn btn-primary">View Questionnaire</button>
      </div>
    `);
    footer_el.querySelector("button[name=back]").addEventListener("click", this.on_navigate_to_parent.bind(this));
    return footer_el;
  }
}

export class CN_view_qnaire extends CN_action_view {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    // show or hide mass-method button based on whether this is a Pine-based qnaire
    const mass_method_btn_el = this.get_footer_element().querySelector("button[name=mass-method]");
    if (mass_method_btn_el) {
      if (this.get_model().allow_edit() && this.get_property_value("pine_qnaire_id")) {
        mass_method_btn_el.classList.remove("d-none");
      } else {
        mass_method_btn_el.classList.add("d-none");
      }
    }
  }

  /**
   * Extend parent method
   */
  create_footer_element() {
    const footer_el = super.create_footer_element();
    const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]");

    // add the mass interview method action
    const mass_method_btn_el = this.constructor.html(`
      <button name="mass-method" type="button" class="btn btn-light btn-outline-primary d-none">
        Mass Interview Method
      </button>
    `);
    mass_method_btn_el.addEventListener("click", async () => {
      await CN_session.navigate_to(
        this.get_model().get_view_url().replace(/qnaire\/view/, "qnaire/mass_method")
      )
    });
    left_btn_group_el.append(mass_method_btn_el);

    return footer_el;
  }
}
