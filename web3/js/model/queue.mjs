const { CN_base_model } = await import(`${CENOZO_URL}/js/model/base_model.mjs`);

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
        participant_count: { title: "Participants", type: "number", table_prefix: false },
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
