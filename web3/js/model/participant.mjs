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
}
