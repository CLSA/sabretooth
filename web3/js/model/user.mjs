const { CN_action_view } = await import(`${CENOZO_URL}/js/action/view.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/user.mjs`);

export class CN_model_user extends classes.CN_model_user {
  /**
   * Extends parent method
   */
  async clone_properties() {
    const properties = await super.clone_properties();
    const is_trainee = 1 == CN_session.get("role", "tier") && CN_session.get("user", "trainee_user");

    properties.trainee_user = {
      title: "Trainee User",
      meta: {},
      type: "boolean",
      required: true,
      get_default: () => false,
      help: "Whether the user is a trainee.",
      is_hidden: () => is_trainee,
    };

    // hide most properties when the user is a trainee
    properties.active.is_hidden = () => is_trainee;
    properties.login_failures.is_hidden = () => is_trainee;
    properties.email.is_hidden = () => is_trainee;
    properties.timezone.is_hidden = () => is_trainee;
    properties.use_12hour_clock.is_hidden = () => is_trainee;

    return properties;
  }
}

const base_view_class = classes.CN_view_user ? classes.CN_view_user : CN_action_view;
export class CN_view_user extends base_view_class {
  /**
   * Extends the parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();

    const is_trainee = 1 == CN_session.get("role", "tier") && CN_session.get("user", "trainee_user");
    const module = CN_session.get_module("appointment");
    if (module.is_root() && module.action_allowed("calendar") && !is_trainee) {
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
