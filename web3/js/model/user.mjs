const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/user.mjs`);

const base_view_class = classes.CN_view_user ? classes.CN_view_user : CN_action_view;
export class CN_view_user extends base_view_class {
  /**
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    // also, finish going through v2 user model to look for missing functionality
    const module = CN_session.get_module("appointment");
    if (module.is_root() && module.action_allowed("calendar")) {
      const calendar_btn_el = this.constructor.html(
        '<button type="button" name="calendar" class="btn btn-light btn-outline-primary">Calendar</button>'
      );
      calendar_btn_el.addEventListener("click", async () => {
        const model = this.get_model();
        await CN_session.navigate_to(`appointment/calendar/user_id=${model.get_identifier()}`);
      });
      footer_el.querySelector("div.btn-group").append(calendar_btn_el);
    }

    return footer_el;
  }
}
