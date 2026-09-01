const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_base_action } = await import(`${CENOZO_URL}/js/action/base_action.mjs`);
const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_common } = await import(`${CENOZO_URL}/js/common.mjs`);
const { CN_element_label } = await import(`${CENOZO_URL}/js/element/label.mjs`);
const { CN_element_loading_box } = await import(`${CENOZO_URL}/js/element/loading_box.mjs`);
const { CN_input_datetimesecond } = await import(`${CENOZO_URL}/js/input/datetimesecond.mjs`);
const { CN_input_enum } = await import(`${CENOZO_URL}/js/input/enum.mjs`);
const { CN_modal_confirm } = await import(`${CENOZO_URL}/js/modal/confirm.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_queue extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "queue",
        plural: "queues",
        posessive: "queue's",
      },
      columns: {
        rank: { title: "Rank", type: "rank" },
        name: { title: "Name" },
        participant_count: { title: "Participants", type: "integer", table_prefix: false },
      },
      properties: {
        rank: { title: "Rank", type: "rank", is_constant: () => true },
        name: { title: "Name", is_constant: () => true },
        title: { title: "Title", is_constant: () => true },
        description: { title: "Description", type: "text", is_constant: () => true },
      },
    });
  }
}

export class CN_tree_queue extends CN_base_action {
  #qnaire_list = [];
  #queue_list = [];
  #queue_tree = [];

  #settings_el;
  #tree_el;
  #repopulated_form_input;
  #qnaire_form_input;
  #site_form_input;
  #language_form_input;

  constructor(parent_el, model) {
    super("tree", parent_el, model);

    this.#repopulated_form_input = new CN_input_datetimesecond(null, {
      id: "repopulated",
      disabled: true,
    });

    this.#qnaire_form_input = new CN_input_enum(null, {
      id: "qnaire_id",
      enum: { path: "qnaire" },
      empty_label: "Any",
      on_change: async (form_input) => {
        this.set_query_parameter("qnaire_id", form_input.get_value_for_record());
        await this.run();
      }
    });
    this.#qnaire_form_input.set_value(this.get_query_parameter("qnaire_id"));

    if (CN_session.get("role", "all_sites")) {
      this.#site_form_input = new CN_input_enum(null, {
        id: "site_id",
        enum: { path: "site" },
        empty_label: "Any",
        on_change: async (form_input) => {
          this.set_query_parameter("site_id", form_input.get_value_for_record());
          await this.run();
        }
      });
      this.#site_form_input.set_value(this.get_query_parameter("site_id"));
    }

    this.#language_form_input = new CN_input_enum(null, {
      id: "language_id",
      enum: {
        path: "language",
        modifier: {
          where: { column: "active", operator: "=", value: true },
          order: "language.name",
        },
      },
      empty_label: "Any",
      on_change: async (form_input) => {
        this.set_query_parameter("language_id", form_input.get_value_for_record());
        await this.run();
      }
    });
    this.#language_form_input.set_value(this.get_query_parameter("language_id"));
  }

  /**
   * Extend parent method
   */
  async get_text(type) {
    if (["crumb", "header"].includes(type)) {
      return "Queue Tree";
    }

    return super.get_text(type);
  }

  /**
   * Replace parent method
   */
  show_placeholder() {
    // operate on the queue tree instead of showing the action's regular placeholder
    if (0 < this.#queue_tree.length) {
      // black out all button titles if the tree is already build
      this.#tree_el.querySelectorAll("button[name=view]").forEach(btn_el => btn_el.innerHTML = "\u2026");
      this.#queue_list.forEach((item, index, array) => {
        array[index].participant_count = 0;
        array[index].child_total = 0;
      });
    } else {
      // show loading box if the tree hasn't been built yet
      CN_element_loading_box.append(this.#tree_el);
    }
  }

  /**
   * Extend parent method
   */
  async on_load(repopulate = "time") {
    this.show_placeholder();

    // get the queue details
    const where = [];
    const qnaire_id = this.#qnaire_form_input.get_value();
    if (qnaire_id) where.push({ column: "qnaire_id", operator: "=", value: qnaire_id })
    const site_id = CN_session.get("role", "all_sites") ? this.#site_form_input.get_value() : null;
    if (site_id) where.push({ column: "site_id", operator: "=", value: site_id })
    const language_id = this.#language_form_input.get_value();
    if (language_id) where.push({ column: "language_id", operator: "=", value: language_id })
    const response = await CN_api.get("queue", {
      full: 1,
      repopulate: repopulate,
      select: { column: ["id", "parent_queue_id", "rank", "name", "title", "participant_count"] },
      modifier: { order: "id", where: where },
    });

    if (0 < this.#queue_tree.length) {
      // don't rebuild the queue, just update the participant totals
      response.forEach(item => {
        const queue = this.#queue_list[item.id - 1];
        queue.participant_count = item.participant_count;
        queue.last_repopulation = item.last_repopulation;
      });
    } else {
      // create an array containing all branches and add their child branches as we go
      let eligible_queue_id = null;
      let old_queue_id = null;
      response.forEach(item => {
        // make note of certain queues
        if (null === eligible_queue_id && "eligible" == item.name) eligible_queue_id = item.id;
        if (null === old_queue_id && "old participant" == item.name) old_queue_id = item.id;

        // add all branches to the root, for now
        item.branch_list = []; // will be filled in if the branch has any children
        item.last = false;
        item.initial_open = null === old_queue_id || old_queue_id > item.id;
        this.#queue_list[item.id - 1] = item;
        if (null !== item.parent_queue_id && "qnaire" != item.name) {
          if ("qnaire" == this.#queue_list[item.parent_queue_id - 1].name) {
            item.parent_queue_id = eligible_queue_id;
          }
          this.#queue_list[item.parent_queue_id - 1].branch_list.push(item);
        }
      });

      // now mark all last-branches and put all root branches into the queue tree
      this.#queue_list.forEach(item => {
        const count = item.branch_list.length;
        if (0 < count) item.branch_list[count - 1].last = true;
        if (item && null === item.parent_queue_id) this.#queue_tree.push(item)
      });
    }

    // check for count errors
    this.#queue_list.forEach((queue, index, array) => {
      // update the repopulation time while we're at it
      if ("all" == queue.name) this.#repopulated_form_input.set_value(queue.last_repopulation);

      if (queue && 0 < queue.branch_list.length) {
        array[index].child_total = queue.branch_list.reduce((count, branch) => {
          count += branch.participant_count;
          return count;
        }, 0);

        if (queue.child_total != queue.participant_count) {
          console.error(
            `Queue "${queue.title}" has ${queue.participant_count} participants ` +
            `but child queues add up to ${queue.child_total} (they should be equal)`
          );
        }
      }
    });
  }

  /**
   * Extend parent method
   */
  update_element() {
    super.update_element();

    if (this.#tree_el) {
      if (0 == this.#tree_el.innerHTML.length) {
        this.build_tree();
      } else {
        // update the tree participant counts (ignore the qnaire queue, it isn't shown in the tree)
        this.#queue_list.filter(queue => "qnaire" != queue.name).forEach(queue => {
          document.getElementById(`node-${queue.id}-btn`).innerHTML = queue.participant_count;
        });
      }
    }
  }

  /**
   * Extend parent method
   */
  build_tree() {
    if (0 == this.#queue_tree.length) return;

    const add_node = (node, parent_el, depth) => {
      // build a list of tree images to display before the node buttons
      const image_list = [];
      for (let b = depth; b > 0; b--) {
        if (b == depth) {
          image_list.unshift(`<img src="${CENOZO_URL}/img/${node.last ? "last_" : ""}branch.png"></img>`);
        } else {
          // get the working node at this depth
          let wnode = node;
          for (let i = 0; i < (depth - b); i++) wnode = this.#queue_list[wnode.parent_queue_id - 1];
          image_list.unshift(`<img src="${CENOZO_URL}/img/${wnode.last ? "empty" : "no"}_branch.png"></img>`);
        }
      }

      const node_el = this.constructor.html(`
        <div class="accordion accordion-flush p-0">
          <div class="accordion-item">
            <div class="accordion-header">
              ${image_list.join("")}<div class="btn-group"></div>
              <label class="col-form-label ps-1 ${node.rank ? "text-success" : ""}">
                ${node.rank ? `Q${node.rank}: ` : ""}${node.title}
              </label>
            </div>
          </div>
        </div>
      `);
      const btn_group_el = node_el.querySelector("div.btn-group");

      // for nodes with branches add the toggle button
      if (0 < node.branch_list.length) {
        btn_group_el.append(this.constructor.html(`
          <button
            type="button"
            name="toggle"
            class="btn btn-primary ${node.initial_open ? "" : "collapsed"}"
            data-bs-toggle="collapse"
            data-bs-target="#node-${node.id}-children"
            aria-expanded="${node.initial_open ? "true" : "false"}"
            aria-controls="node-${node.id}-children"
          >
            <i class="bi bi-chevron-${node.initial_open ? "down" : "up"}"></i>
          </button>
        `));

        // change toggle chevron direction when opening/closing branch
        const toggle_btn_el = node_el.querySelector("button[name=toggle]");
        toggle_btn_el.addEventListener("click", () => {
          const i_el = toggle_btn_el.querySelector("i");
          if (i_el.classList.contains("bi-chevron-down")) {
            i_el.classList.remove("bi-chevron-down");
            i_el.classList.add("bi-chevron-up");
          } else {
            i_el.classList.remove("bi-chevron-up");
            i_el.classList.add("bi-chevron-down");
          }
        });
      }

      // add the view button for every queue, including how many participants are in the queue
      btn_group_el.append(this.constructor.html(`
        <button
          id="node-${node.id}-btn"
          type="button"
          name="view"
          class="btn ${node.rank ? "btn-success" : "btn-light btn-outline-primary"}"
        >${node.participant_count}</button>
      `))
      const view_btn_el = node_el.querySelector("button[name=view]");
      view_btn_el.addEventListener("click", () => {
        const columns = {};
        const qnaire_name = this.#qnaire_form_input.get_value_label();
        if (this.#qnaire_form_input.get_value()) {
          columns.qnaire = [{ operator: "=", value: this.#qnaire_form_input.get_value_label(), or: false }];
        }
        if (CN_session.get("role", "all_sites") && this.#site_form_input.get_value()) {
          columns.site = [{ operator: "=", value: this.#site_form_input.get_value_label(), or: false }];
        }
        if (this.#language_form_input.get_value()) {
          columns.language = [{ operator: "=", value: this.#language_form_input.get_value_label(), or: false }];
        }

        CN_session.navigate_to(
          `queue/view/${node.id}`,
          0 < Object.keys(columns).length ?
          { tables: JSON.stringify({ participant: { columns: columns } }) } :
          null
        );
      });

      parent_el.append(node_el);

      // now add the node's branches
      if (0 < node.branch_list.length) {
        const children_el = this.constructor.html(`
          <div id="node-${node.id}-children" class="accordion-collapse collapse">
            <div class="accordion-body p-0"></div>
          </div>
        `);
        if (node.initial_open) children_el.classList.add("show");
        node_el.querySelector("div.accordion-item").append(children_el);
        const sub_el = children_el.querySelector("div.accordion-body");
        node.branch_list.forEach((child_node, child_index) => {
          add_node(child_node, sub_el, depth + 1);
        });
      }
    };

    this.#tree_el.innerHTML = "";
    this.#queue_tree.forEach(node => add_node(node, this.#tree_el, 0));
  }

  /**
   * Extend parent method
   */
  _create_body_element() {
    const body_el = this.constructor.html(`
      <div class="row">
        <div name="settings" class="col-sm-4"></div>
        <div name="tree" class="col-sm-8"></div>
      </div>
    `);

    this.#settings_el = body_el.querySelector("div[name=settings]");
    this.#tree_el = body_el.querySelector("div[name=tree]");

    // add last re-populated time input
    let row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "repopulated",
      value: "Last Re-Population",
    }).get_element().classList.remove("text-end");
    this.#repopulated_form_input.set_parent_element(row_el);
    row_el.append(this.#repopulated_form_input.get_element());
    this.#settings_el.append(row_el);

    if (3 <= CN_session.get("role", "tier")) {
      // add re-populate button
      row_el = this.constructor.html(`
        <div class="row mx-0 my-3">
          <button type="button" name="repopulate" class="btn btn-warning">Re-Populate</button>
          <div class="alert alert-info" role="alert">
            The queue tree automatically re-populates itself once a day.
            The last time the queue was re-popluated is shown above.
            The queue tree can be manually re-populated by clicking the button above.
          </div>
        </div>
      `);
      row_el.querySelector("button[name=repopulate]").addEventListener("click", async () => {
        const response = await CN_modal_confirm.create_and_open({
          title: "Re-Populate Queue",
          message: `
            Are you sure you wish to repopulate all queues?
            <br>\n
            <br>\n
            This will prevent assignments from being started or finished and generally slow down all
            applications until it is finished processing.This may cause applications to slow down until
            the processing is complete.
          `,
        });

        if (response) {
          await this.constructor.wait_for(async () => {
            await this.on_load("full");
            this.update_element();
          }, 0);
        }
      });
      this.#settings_el.append(row_el);
    }

    // add qnaire input
    row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "qnaire_id",
      value: "Questionnaire",
    }).get_element().classList.remove("text-end");
    this.#qnaire_form_input.set_parent_element(row_el);
    row_el.append(this.#qnaire_form_input.get_element());
    this.#settings_el.append(row_el);

    // add site input
    if (CN_session.get("role", "all_sites")) {
      row_el = this.constructor.html('<div class="row"></div>');
      CN_element_label.append(row_el, {
        for: "site_id",
        value: "Site",
      }).get_element().classList.remove("text-end");
      this.#site_form_input.set_parent_element(row_el);
      row_el.append(this.#site_form_input.get_element());
      this.#settings_el.append(row_el);
    }

    // add language input
    row_el = this.constructor.html('<div class="row"></div>');
    CN_element_label.append(row_el, {
      for: "language_id",
      value: "Participant Language",
    }).get_element().classList.remove("text-end");
    this.#language_form_input.set_parent_element(row_el);
    row_el.append(this.#language_form_input.get_element());
    this.#settings_el.append(row_el);

    this.#settings_el.append(this.constructor.html(`
      <div class="card bg-body-secondary mt-3">
        <div class="card-body">
          Each branch of the tree represents a queue and includes the total number of
          participants belonging to that queue (refined by the settings above).
          <br/>
          <br/>
          Ranked queues are denoted by their rank (Q<em>n</em>) and highlighted.
          <br/>
          <br/>
          A participant must belong to a ranked queue in order to be assigned for interviewing.
          </ul>
        </div>
      </div>
    `));

    return body_el;
  }
}
