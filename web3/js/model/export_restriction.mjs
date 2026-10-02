const { CN_api } = await import(`${CENOZO_URL}/js/api.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);
const classes = await import(`${CENOZO_URL}/js/model/export_restriction.mjs`);

export class CN_list_export_restriction extends classes.CN_list_export_restriction {
  constructor(parent_el, model) {
    super("list", parent_el, model);

    if (CN_session.get_module("interview")) {
      this.add_table("interview", {
        column_enum_list: null,
        subtype_promise: CN_api.get("qnaire", {
          select: { column: { column: 'CONCAT(rank, ". ", script.name)', alias: "value", table_prefix: false } },
          modifier: { order: "rank" },
        }),
      });
    }
  }
}
