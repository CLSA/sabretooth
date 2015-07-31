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
    site_id: {
      title: 'Credited Site',
      type: 'enum',
      help: 'This determines which site is credited with the completed interview.'
    },
    interview_method_id: {
      title: 'Interview Method',
      type: 'enum'
    },
    start_datetime: {
      column: 'interview.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      max: 'end_datetime',
      help: 'When the first call from the first assignment was made for this interview.'
    },
    end_datetime: {
      column: 'interview.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      min: 'start_datetime',
      max: 'now',
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
    site: {
      column: 'site.name',
      title: 'Site'
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
    column: 'interview.start_datetime',
    reverse: false
  }
} );
