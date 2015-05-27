define( {
  subject: 'assignment',
  name: {
    singular: 'assignment',
    plural: 'assignments',
    possessive: 'assignment\'s',
    pluralPossessive: 'assignments\''
  },
  inputList: {
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
    participant: {
      column: 'participant.uid',
      title: 'Participant',
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
    uid: {
      column: 'interview.participant.uid',
      title: 'UID'
    },
    start_datetime: {
      column: 'assignment.start_datetime',
      title: 'Start Time',
      filter: 'cnMomentDate:"MMM D, YYYY @ HH:mm"',
    },
    status: {
      title: 'Status'
    },
    complete: {
      column: 'interview.completed',
      title: 'Complete',
      filter: 'cnYesNo'
    }
  },
  defaultOrder: {
    column: 'start_datetime',
    reverse: true
  }
} );
