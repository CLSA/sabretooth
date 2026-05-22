const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/assignment.mjs`);

export class CN_model_assignment extends classes.CN_model_assignment {
  /**
   * Extend parent method
   */
  clone_properties() {
    const properties = super.clone_properties();

    CN_common.insert_property_after(properties, "participant", "qnaire", {
      meta: { table: "script", column: "name" },
      title: "Questionnaire",
      is_constant: () => true,
    });

    CN_common.insert_property_after(properties, "site", "queue", {
      meta: { table: "queue", column: "title" },
      title: "Queue",
      is_constant: () => true,
    });

    return properties;
  }
}
