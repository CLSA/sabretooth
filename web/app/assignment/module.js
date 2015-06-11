define( {
  subject: 'assignment',
  identifier: {
    parent: {
      subject: 'interview',
      column: 'interview_id'
    }
  },
  name: {
    singular: 'assignment',
    plural: 'assignments',
    possessive: 'assignment\'s',
    pluralPossessive: 'assignments\''
  },
  inputList: {
    participant: {
      column: 'participant.uid',
      title: 'Participant',
      type: 'string',
      constant: true
    },
    qnaire: {
      column: 'qnaire.name',
      title: 'Questionnaire',
      type: 'string',
      constant: true
    },
    user: {
      column: 'user.name',
      title: 'User',
      type: 'string',
      constant: true
    },
    site: {
      column: 'site.name',
      title: 'Site',
      type: 'string',
      constant: true
    },
    queue: {
      column: 'queue.title',
      title: 'Queue',
      type: 'string',
      constant: true
    },
    start_datetime: {
      column: 'assignment.start_datetime',
      title: 'Start Date & Time',
      type: 'datetimesecond',
      constant: true
    },
    end_datetime: {
      column: 'assignment.end_datetime',
      title: 'End Date & Time',
      type: 'datetimesecond',
      constant: true
    }
  },
  columnList: {
    user: {
      column: 'user.name',
      title: 'Operator'
    },
    site: {
      column: 'site.name',
      title: 'Site'
    },
    start_datetime: {
      column: 'assignment.start_datetime',
      title: 'Start',
      filter: 'cnMomentDate:"MMM D, YYYY @ HH:mm"',
    },
    end_datetime: {
      column: 'assignment.end_datetime',
      title: 'End',
      filter: 'cnMomentDate:"MMM D, YYYY @ HH:mm"',
    }
  },
  defaultOrder: {
    column: 'start_datetime',
    reverse: true
  }
} );
