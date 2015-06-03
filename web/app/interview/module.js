define( {
  subject: 'interview',
  name: {
    singular: 'interview',
    plural: 'interviews',
    possessive: 'interview\'s',
    pluralPossessive: 'interviews\''
  },
  inputList: {
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      constant: true
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      constant: true
    }
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
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start',
      filter: 'cnMomentDate:"MMM D, YYYY @ HH:mm"',
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End',
      filter: 'cnMomentDate:"MMM D, YYYY @ HH:mm"',
    }
  },
  defaultOrder: {
    column: 'uid',
    reverse: false
  }
} );
