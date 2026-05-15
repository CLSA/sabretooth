const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/interview.mjs`);

export class CN_model_interview extends classes.CN_model_interview {
  /** 
   * Extend parent method
   */
  clone_properties() {
    // TODONEXT: these are supposed to be added to the columns, not properties
    const properties = super.clone_properties();

    CN_common.insert_property_after(properties, "participant", "qnaire", {
      meta: { table: "script", column: "name" },
      title: "Questionnaire",
    });
    CN_common.insert_property_after(properties, "qnaire", "method", {
      meta: { table: "interview", column: "method" },
      title: "Method",
    });
    CN_common.insert_property_after(properties, "site_id", "page_progress", {
      meta: {},
      title: "Page Progress",
    });

    return properties;
  }
}
