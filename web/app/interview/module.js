define( {
  subject: 'interview',
  identifier: {
    parent: {
      subject: 'participant',
      column: 'participant.uid'
    }
  },
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
    qnaire_id: {
      title: 'Questionnaire',
      type: 'enum',
      constant: true
    },
    interview_method_id: {
      title: 'Interview Method',
      type: 'enum'
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      help: 'When the first call from the first assignment was made for this interview.'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      help: 'Will remain blank until the questionnaire is complete.'
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
      type: 'datetimesecond'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End',
      type: 'datetimesecond'
    }
  },
  defaultOrder: {
    column: 'participant.uid',
    reverse: false
  }
} );
