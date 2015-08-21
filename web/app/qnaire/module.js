define( {
  subject: 'qnaire',
  identifier: { column: 'name' },
  name: {
    singular: 'questionnaire',
    plural: 'questionnaires',
    possessive: 'questionnaire\'s',
    pluralPossessive: 'questionnaires\''
  },
  inputList: {
    name: {
      title: 'Name',
      type: 'string'
    },
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    prev_qnaire_id: {
      title: 'Previous Questionnaire',
      type: 'enum',
      noself: true // previous qnaire cannot be itself
    },
    delay: {
      title: 'Delay (weeks)',
      type: 'string',
      format: 'integer',
      minValue: 0
    },
    withdraw_sid: {
      title: 'Withdraw Survey',
      type: 'enum'
    },
    description: {
      title: 'Description',
      type: 'text'
    }
  },
  columnList: {
    name: {
      column: 'qnaire.name',
      title: 'Name'
    },
    rank: {
      column: 'qnaire.rank',
      title: 'Rank',
      type: 'rank'
    },
    previous: {
      column: 'prev_qnaire.name',
      title: 'Previous'
    },
    delay: {
      column: 'qnaire.delay',
      title: 'Delay',
      type: 'number'
    },
    phase_count: {
      title: 'Phases',
      type: 'number'
    }
  },
  defaultOrder: {
    column: 'qnaire.rank',
    reverse: false
  }
} );
