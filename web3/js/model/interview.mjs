const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/interview.mjs`);

export class CN_model_interview extends classes.CN_model_interview {
  /**
   * Extend parent method
   */
  clone_columns() {
    const columns = super.clone_columns();

    CN_common.insert_property(columns, "after", "uid", "qnaire", {
      column: "script.name",
      title: "Questionnaire",
    });
    CN_common.insert_property(columns, "after", "qnaire", "method", {
      column: "interview.method",
      title: "Method",
    });
    CN_common.insert_property(columns, "after", "site", "page_progress", {
      title: "Page Progress",
      table_prefix: false,
    });

    return columns;
  }

  /**
   * Extend parent method
   */
  clone_properties() {
    const properties = super.clone_properties();

    CN_common.insert_property(properties, "after", "uid", "method", {
      title: "Interviewing Method",
      type: "enum",
      is_constant: () => true,
    });

    CN_common.insert_property(properties, "after", "uid", "qnaire", {
      meta: { table: "script", column: "name" },
      title: "Questionnaire",
      is_constant: () => true,
    });

    CN_common.insert_property(properties, "after", "site_id", "page_progress", {
      meta: {}, // defined on the server side
      title: "Page Progress",
      is_constant: () => true,
    });

    // properties needed by the appointment model
    properties.last_participation_consent = { meta: {}, type: "boolean", is_hidden: () => true };
    properties.future_appointment = { meta: {}, type: "boolean", is_hidden: () => true };

    return properties;
  }

  /**
   * Extend parent method
   */
  allow_add() {
    const action = this.get_action();

    // only allow adding a new interview if one is available
    return (
      super.allow_add() &&
      "list" == action.get_type() &&
      action.is_new_interview_available()
    );
  }

  /**
   * Extend parent method
   */
  allow_edit() {
    return ["administrator", "helpline"].includes(CN_session.get("role", "name"));
  }
}

export class CN_list_interview extends classes.CN_list_interview {
  #current_queue_rank = null;
  #current_qnaire_rank = null;
  #open_interview_count = null;

  /**
   * Extend parent method
   */
  async on_load() {
    await super.on_load();

    this.#current_queue_rank = null;
    this.#current_qnaire_rank = null;
    this.#open_interview_count = null;

    // Make note of the number of open interviews so we know when an interview can be added.
    // Note that this has to happen before calling the parent class' run method so the value is up to date when
    // the update_element() method is called.
    const parent_model = this.get_model().get_parent_model();
    if (parent_model && "participant" == parent_model.get_name()) {
      const [participant_response, interview_count] = await Promise.all([
        CN_api.get(parent_model.get_view_url(null, "api"), {
          select: { column: [
            { table: "queue", column: "rank", alias: "queue_rank" },
            { table: "qnaire", column: "rank", alias: "qnaire_rank" },
          ]},
        }),

        CN_api.count(`${parent_model.get_view_url(null, "api")}/interview`, {
          modifier: { where: { column: "end_datetime", operator: "=", value: null } }
        }),
      ]);

      this.#current_queue_rank = participant_response.queue_rank;
      this.#current_qnaire_rank = participant_response.qnaire_rank;
      this.#open_interview_count = interview_count;
    }
  }

  /**
   * Determines if a new interview is available
   * (there are no open interviews and the participant is still in a queue and qnaire)
   */
  is_new_interview_available() {
    return (
      0 === this.#open_interview_count &&
      null != this.#current_queue_rank &&
      null != this.#current_qnaire_rank
    );
  }
}

export class CN_view_interview extends classes.CN_view_interview {
  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    const complete_btn_el = this.get_footer_element().querySelector("button[name=complete]");
    if (complete_btn_el) {
      this.constructor.set_disabled(complete_btn_el, "(empty)" != this.get_property_value("end_datetime"));
    }
  }

  /**
   * Extend parent method
   */
  _create_footer_element() {
    const footer_el = super._create_footer_element();
    const model = this.get_model();

    if (2 < CN_session.get("role", "tier")) {
      const left_btn_group_el = footer_el.querySelector("div[name=left-btn-group]")

      // add the remove action
      const remove_btn_el = this.constructor.html(`
        <button
          name="remove"
          type="button"
          class="btn btn-danger"
          data-bs-toggle="tooltip"
          data-bs-html="true"
          data-bs-title="
            <div class='fw-bold'>Force removes the interview:</div>
            This will delete all appointments, calls, and assignments along with the interview.
            Questionnaires associated with the interview will not be changed and must be edited/deleted directly.
          "
        >Force Remove</button>
      `);
      new bootstrap.Tooltip(remove_btn_el);
      remove_btn_el.addEventListener("click", async () => {
        const response = await CN_modal_confirm.create_and_open({
          title: "Force Remove Interview?",
          message: `
            Are you sure you wish to force-remove the interview?
            <br/>
            <br/>
            Note that all appointments, phone calls, and assignments associated with the
            interview will also be deleted.  Questionnaires will not be changed, and start/finish
            events will not be deleted.  This operation cannot be undone.
          `,
        });

        if (response) {
          try {
            await CN_api.patch(`${model.get_view_url(null, "api")}?operation=force_delete`, {});
            await this.on_navigate_to_parent();
          } catch (error) {
            if (409 == error.response.status) {
            } else {
              throw error;
            }
          }
        }
      });
      left_btn_group_el.append(remove_btn_el);

      // add the complete action
      const complete_btn_el = this.constructor.html(`
        <button
          name="complete"
          type="button"
          class="btn btn-danger"
          data-bs-toggle="tooltip"
          data-bs-html="true"
          data-bs-title="
            <div class='fw-bold'>Force completes the interview:</div>
            This will end the interview's questionnaire leaving any remaining questions unanswered.
            You should only force-close an interview when you are sure that as many questions in the
            questionnaire has been answered as possible and there is no reason to re-assign the participant.
          "
        >Force Complete</button>
      `);
      new bootstrap.Tooltip(complete_btn_el);
      complete_btn_el.addEventListener("click", async () => {
        const response = await CN_modal_confirm.create_and_open({
          title: "Force Complete Interview?",
          message: `
            Are you sure you wish to force-complete the interview?
            <br/>
            <br/>
            Note that the interview's questionnaire will be closed and unanswered questions will
            no longer be accessible.  This operation cannot be undone.
          `
        });

        if (response) {
          try {
            await CN_api.patch(`${model.get_view_url(null, "api")}?operation=force_complete`, {});
            await this.run();
          } catch (error) {
            if (409 == error.response.status) {
            } else {
              throw error;
            }
          }
        }
      });
      left_btn_group_el.append(complete_btn_el);
    }

    return footer_el;
  }
}
