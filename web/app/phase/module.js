define( {
  subject: 'phase',
  identifier: {
    parent: {
      subject: 'qnaire',
      column: 'qnaire_id'
    }
  },
  name: {
    singular: 'phase',
    plural: 'phases',
    possessive: 'phase\'s',
    pluralPossessive: 'phases\'',
    friendlyColumn: 'rank'
  },
  inputList: {
    sid: {
      title: 'Survey',
      type: 'enum'
    },
    rank: {
      column: 'phase.rank',
      title: 'Stage',
      type: 'rank'
    },
    repeated: {
      title: 'Repeated',
      type: 'boolean'
    }
  },
  columnList: {
    survey_title: {
      title: 'Name'
    },
    rank: {
      column: 'phase.rank',
      title: 'Stage',
      type: 'rank'
    },
    repeated: {
      title: 'Repeated',
      type: 'boolean'
    }
  },
  defaultOrder: {
    column: 'phase.rank',
    reverse: false
  }
} );
