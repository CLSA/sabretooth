const { CN_action_list } = await import(`${CENOZO_URL}/js/action/list.mjs`);
const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/site.mjs`);

const base_list_class = classes.CN_list_site ? classes.CN_list_site : CN_action_list;
export class CN_list_site extends base_list_class {
  /**
   * Extend parent method
   */
  async get_text(type) {
    const text = await super.get_text(type);
    return "header" == type && "qnaire" == CN_session.get_leaf_model().get_name() ? `Disabled ${text}` : text;
  }
}

const base_view_class = classes.CN_view_site ? classes.CN_view_site : CN_action_view;
export class CN_view_site extends base_view_class {
  /**
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    const settings_btn_el = this.constructor.html(
      '<button type="button" name="settings" class="btn btn-light btn-outline-primary">Settings</button>'
    );
    settings_btn_el.addEventListener("click", async () => {
      const model = this.get_model();
      await CN_session.navigate_to(`${model.get_view_url()}/setting/view/site_id=${model.get_identifier()}`);
    });
    footer_el.querySelector("div.btn-group").append(settings_btn_el);

    return footer_el;
  }
}
