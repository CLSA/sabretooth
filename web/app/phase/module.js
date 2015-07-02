define( {
  subject: 'phase',
  identifier: {}, // standard
  name: {
    singular: 'phase',
    plural: 'phases',
    possessive: 'phase\'s',
    pluralPossessive: 'phases\''
  },
  inputList: {
    sid: {
      title: 'Default Survey',
      type: 'enum'
    },
    rank: {
      title: 'Stage',
      type: 'rank'
    },
    repeated: {
      title: 'Repeated',
      type: 'boolean'
    }
  },
  columnList: {
    survey_name: {
      title: 'Name'
    },
    rank: {
      title: 'Stage',
      type: 'rank'
    },
    repeated: {
      title: 'Repeated',
      type: 'boolean'
    }
  },
  defaultOrder: {
    column: 'rank',
    reverse: false
  }
} );
