define( {
  subject: 'phone_call',
  identifier: {
    parent: {
      subject: 'assignment',
      column: 'assignment_id'
    }
  },
  name: {
    singular: 'phone call',
    plural: 'phone calls',
    possessive: 'phone call\'s',
    pluralPossessive: 'phone calls\''
  },
  inputList: {
    // not used
  },
  columnList: {
    phone: {
      column: 'phone.type',
      title: 'Phone'
    },
    start_datetime: {
      column: 'phone_call.start_datetime',
      title: 'Start',
      type: 'datetimesecond',
      max: 'now'
    },
    end_datetime: {
      column: 'phone_call.end_datetime',
      title: 'End',
      type: 'datetimesecond',
      max: 'now'
    },
    status: { title: 'Status' }
  },
  defaultOrder: {
    column: 'start_datetime',
    reverse: true
  }
} );
