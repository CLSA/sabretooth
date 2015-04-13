define( {
  subject: 'qnaire',
  name: {
    singular: 'questionnaire',
    plural: 'questionnaires',
    possessive: 'questionnaire\'s',
    pluralPossessive: 'questionnaires\''
  },
  inputList: {
    // TODO: fill out
  },
  columnList: {
    name: {
      column: 'qnaire.name',
      title: 'Name'
    },
    rank: {
      column: 'qnaire.rank',
      title: 'Rank' },
    method: {
      column: 'default_interview_method.name',
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
    phase_count: { title: 'Phases' }
  },
  defaultOrder: {
    column: 'rank',
    reverse: false
  }
} );
