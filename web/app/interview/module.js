define( {
  subject: 'interview',
  name: {
    singular: 'interview',
    plural: 'interviews',
    possessive: 'interview\'s',
    pluralPossessive: 'interviews\''
  },
  inputList: {
    // TODO: fill out
  },
  columnList: {
    uid: {
      column: 'participant.uid',
      title: 'UID'
    },
    qnaire: {
      column: 'qnaire.name',
      title: 'Questionnaire'
    },
    method: {
      column: 'interview_method.name',
      title: 'Method'
    },
    completed: {
      title: 'Completed',
      filter: 'cnYesNo'
    },
    date: {
      column: 'interview.id',
      title: 'TODO'
    },
  },
  defaultOrder: {
    column: 'uid',
    reverse: false
  }
} );
