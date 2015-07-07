define( {
  subject: 'qnaire',
  identifier: {}, // standard
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
    interview_method_id: {
      title: 'Default Interview Method',
      type: 'enum'
    },
    prev_qnaire_id: {
      title: 'Previous Questionnaire',
      type: 'enum'
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
    method: {
      column: 'interview_method.name',
      title: 'Method'
    },
    previous: {
      column: 'prev_qnaire.name',
      title: 'Previous'
    },
    delay: {
      column: 'qnaire.delay',
      title: 'Delay'
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
