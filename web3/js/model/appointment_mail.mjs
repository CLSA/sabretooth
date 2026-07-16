const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);
const { CN_session } = await import(`${CENOZO_URL}/js/session.mjs`);

export class CN_model_appointment_mail extends CN_base_model {
  constructor() {
    super({
      wording: {
        singular: "appointment mail template",
        plural: "appointment mail templates",
        posessive: "appointment mail template's",
      },
      columns: {
        site: { column: "site.name", title: "Site" },
        language: { column: "language.name", title: "Language" },
        delay: { title: "Delay", table_prefix: false },
        subject: { title: "Subject" },
      },
      get_default_order: () => "delay",
      properties: {
        site_id: {
          title: "Site",
          type: "enum",
          enum: { path: "site" },
          is_hidden: (model) => "add" == model.get_action_name() || !CN_session.get("role", "all_sites"),
          is_constant: (model) => "view" == model.get_action_name(),
        },
        language_id: {
          title: "Language",
          type: "enum",
          enum: {
            path: "language",
            modifier: {
              where: { column: "active", operator: "=", value: true },
              order: "language.name",
            },
          },
          is_constant: (model) => "view" == model.get_action_name(),
        },
        from_name: { title: "From Name" },
        from_address: {
          title: "From Address",
          format: "eappointment_mail",
          help: 'Must be in the format "account@domain.name".',
        },
        cc_address: {
          title: "Carbon Copy (CC)",
          help: `
            May be a comma-delimited list of email addresses in the format "account@domain.name".
          `,
        },
        bcc_address: {
          title: "Blind Carbon Copy (BCC)",
          help: 'May be a comma-delimited list of email addresses in the format "account@domain.name".',
        },
        delay_offset: {
          title: "Delay (days)",
          format: "integer",
          is_hidden:
            (model) => "add" == model.get_action_name() ||
            "immediately" == model.get_action().get_property_value("delay_unit"),
        },
        delay_unit: { title: "Delay Type", type: "enum" },
        subject: { title: "Subject" },
        body: { title: "Body", type: "text" },
      },
    });
  }
}
