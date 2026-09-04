const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);

const classes = await import(`${CENOZO_URL}/js/model/overview.mjs`);

export class CN_view_overview extends classes.CN_view_overview {
  /**
   * Extend parent method
   */
  get_on_load_parameters() {
    let params = super.get_on_load_parameters();

    // add the study restriction to the progress overview
    const query = this.get_query_parameter("study_id");
    if (query) {
      if (!CN_common.is_object(query)) params = {};
      params.data_modifier = CN_api.modifier({ where: { column: "study.id", operator: "=", value: query } });
    }

    return params;
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const study_div_el = this.get_element().querySelector("div[name=study]");
    if ("progress" == this.get_record().name) {
      study_div_el.classList.remove("d-none");
    } else {
      study_div_el.classList.add("d-none");
    }
  }

  /**
   * Extend parent method
   */
  _create_element() {
    const el = super._create_element();

    const row_el = this.constructor.html('<div name="study" class="d-none d-flex mx-1 my-2"></div>');
    CN_element_label.append(row_el, { for: "study_id", value: "Restrict to Study", class: "me-3" });
    CN_input_enum.append(row_el, {
      id: "study_id",
      required: false,
      class: "flex-fill",
      get_default: () => this.get_query_parameter("study_id"),
      enum: { path: "study" },
      on_change: async (form_input, valid) => {
        this.set_query_parameter("study_id", form_input.get_value());
        await this.run();
      },
    });
    el.prepend(row_el);

    return el;
  }
}
