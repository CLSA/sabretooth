const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/phone_call.mjs`);

export class CN_model_phone_call extends classes.CN_model_phone_call {
  /**
   * Extend parent method
   */
  async clone_columns() {
    const columns = await super.clone_columns();

    CN_common.insert_property(columns, "before", "phone", "person", {
      title: "Person",
      is_hidden: () => !CN_session.get("setting", "proxy"), table_prefix: false,
    });

    return columns;
  }
}
