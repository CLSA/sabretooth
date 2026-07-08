const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_base_app_session } = await import(`${CENOZO_URL}/js/base_app_session.mjs`);
const { CN_base_element } = await import(`${CENOZO_URL}/js/element/base_element.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

class app_session extends CN_base_app_session {
  #menu_btn_el;
  #control_btn_el;

  /**
   * Extend parent method
   */
  async render() {
    await super.render();

    // flash the menu and assignment control buttons when in an assignment
    if (CN_session.get("user", "assignment")) {
      this.#menu_btn_el.classList.add("btn-pulse-danger");
      this.#control_btn_el.classList.add("btn-pulse-danger");
    }
  }

  /**
   * Extend parent method
   */
  async start() {
    await super.start();

    this.#menu_btn_el = document
      .getElementById("main-menu-header")
      .querySelector("button[name=menu-button]");
    this.#control_btn_el = document
      .getElementById("main-menu-offcanvas")
      .querySelector('button[name="assignment.control"]');

    // Need to re-define the event handler for the assignment-control menu button when not logged in
    // as an assignment-taking role
    const assignment = CN_session.get("user", "assignment");
    if (
      this.#control_btn_el &&
      null != assignment && (
        CN_session.get("role", "id") != assignment.role_id ||
        CN_session.get("site", "id") != assignment.site_id
      )
    ) {
      // clone the assignment-control button and replace it (to remove the event listener)
      const parent_el = this.#control_btn_el.parentNode;
      const control_btn_el = this.#control_btn_el.cloneNode(true);
      this.#control_btn_el.parentElement.insertBefore(control_btn_el, this.#control_btn_el);
      this.#control_btn_el.remove();
      this.#control_btn_el = control_btn_el;

      // now add the new event listener
      this.#control_btn_el.addEventListener("click", async () => {
        const response = await CN_modal_confirm.create_and_open({
          title: "Switch Roles for Assignment",
          message: `
            You must switch your role and site to <span class="fw-bold">${assignment.role_name}</span>
            at <span class="fw-bold">${assignment.site_name}</span> before you can proceed with your assignment.
            <br/>
            <br/>
            Do you wish to proceed?
          `,
        });
        if (response) {
          CN_session.close_menu();
          await CN_api.patch("self/0", { site: { id: assignment.site_id }, role: { id: assignment.role_id } });
          CN_session.reload("assignment/control");
        }
      });
    }
  }
}

// Now create the app_session singleton and export it
const CN_app_session = new app_session();
export { CN_app_session };
