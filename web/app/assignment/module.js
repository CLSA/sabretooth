define( {
  subject: 'assignment',
  name: {
    singular: 'assignment',
    plural: 'assignments',
    possessive: 'assignment\'s',
    pluralPossessive: 'assignments\''
  },
  inputList: {
    // TODO: fill out
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
      filter: 'date:"MMM d, y HH:mm"',
      isDate: true
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
