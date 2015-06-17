define( {
  subject: 'queue',
  identifier: {}, // standard
  name: {
    singular: 'queue',
    plural: 'queues',
    possessive: 'queue\'s',
    pluralPossessive: 'queues\''
  },
  inputList: {
    // TODO: fill out
  },
  columnList: {
    rank: {
      title: 'Rank',
      type: 'rank'
    },
    name: { title: 'Name' },
    participant_count: {
      title: 'Participants',
      type: 'number'
    }
  },
  defaultOrder: {
    column: 'rank',
    reverse: false
  }
} );
