const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/stratum.mjs`);

const base_list_class = classes.CN_list_stratum ? classes.CN_list_stratum : CN_action_list;
export class CN_list_stratum extends base_list_class {
  /**
   * Extend parent method
   */
  async get_text(type) {
    const text = await super.get_text(type);
    return "header" == type && "qnaire" == CN_session.get_leaf_model().get_name() ? `Disabled ${text}` : text;
  }
}
